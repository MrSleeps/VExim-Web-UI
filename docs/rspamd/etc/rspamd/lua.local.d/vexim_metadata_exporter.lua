local rspamd_logger = require "rspamd_logger"
local http = require "rspamd_http"
local ucl = require "ucl"

local module_cfg = rspamd_config:get_all_opt("vexim") or {}
local api_url = module_cfg.api_url or ""
local api_token = module_cfg.api_key or ""

if api_url ~= "" then
  api_url = api_url:gsub("/$", "") .. "/api/v1/rspamd/metadata"
end

rspamd_config:register_symbol({
  name = 'VEXIM_EXPORT',
  type = 'idempotent',
  callback = function(task)
    if api_url == "" then return end

    local action = task:get_metric_action('default')

    -- Get spam score
    local score_table = task:get_metric_score('default')
    local score = score_table and score_table[1] or 0
    local required_score = score_table and score_table[2] or 5.0

    -- Get symbols
    local symbols_list = {}
    local syms = task:get_symbols_all()
    if syms then
      for _, sym in ipairs(syms) do
        table.insert(symbols_list, sym.name)
      end
    end

    -- Check for virus
    local has_virus = 0
    for _, sym in ipairs(symbols_list) do
      if string.match(sym, "^VIRUS") or string.match(sym, "^CLAM") then
        has_virus = 1
        break
      end
    end

    -- Get envelope sender (prefer SMTP, fall back to MIME)
    local mail_from = ""
    local from = task:get_from('smtp')
    if from and type(from) == 'table' and #from > 0 then
      mail_from = from[1].addr or ""
    else
      local mime_from_smtp = task:get_from('mime')
      if mime_from_smtp and type(mime_from_smtp) == 'table' and #mime_from_smtp > 0 then
        mail_from = mime_from_smtp[1].addr or ""
      end
    end

    -- Get envelope recipients
    local rcpt_list = {}
    local rcpt_to = task:get_recipients('smtp')
    if rcpt_to then
      for _, rcpt in ipairs(rcpt_to) do
        table.insert(rcpt_list, rcpt.addr or "")
      end
    end

    -- Get MIME from
    local mime_from = ""
    local mime_from_raw = task:get_from('mime')
    if mime_from_raw and type(mime_from_raw) == 'table' and #mime_from_raw > 0 then
      mime_from = mime_from_raw[1].raw or mime_from_raw[1].addr or ""
    end

    -- Get MIME to
    local mime_to = ""
    local mime_to_raw = task:get_recipients('mime')
    if mime_to_raw then
      local to_strings = {}
      for _, rcpt in ipairs(mime_to_raw) do
        table.insert(to_strings, rcpt.raw or rcpt.addr or "")
      end
      mime_to = table.concat(to_strings, ", ")
    end

    local subject = task:get_subject() or ""
    local message_id = task:get_message_id() or ""
    local qid = task:get_queue_id() or ""
    local size = task:get_size() or 0

    -- Get connection info
    local ip_obj = task:get_from_ip()
    local ip = ip_obj and tostring(ip_obj) or ""
    local helo = task:get_helo() or ""

    -- Build metadata table
    local metadata = {
      qid            = qid,
      action         = action,
      score          = score,
      required_score = required_score,
      mail_from      = mail_from,
      mime_from      = mime_from,
      rcpt_to        = rcpt_list,
      mime_to        = mime_to,
      subject        = subject,
      message_id     = message_id,
      ip             = ip,
      helo           = helo,
      symbols        = symbols_list,
      has_virus      = has_virus,
      size           = size,
    }

    local json_string = ucl.to_format(metadata, "json")

    rspamd_logger.infox(task, "VEXIM: QID='%s', action='%s', score='%.2f', virus=%s",
      qid, action, score, has_virus == 1 and "YES" or "no")
    rspamd_logger.debugx(task, "VEXIM: payload: %s", json_string)

    local headers = {
      ["Content-Type"]  = "application/json",
      ["Accept"]        = "application/json",
    }

    if api_token ~= "" then
      headers["Authorization"] = "Bearer " .. api_token
    end

    http.request({
      task = task,
      url = api_url,
      method = "POST",
      headers = headers,
      body = json_string,
      callback = function(err, code)
        if err then
          rspamd_logger.errx(task, "VEXIM: HTTP error: %s", err)
        elseif code == 401 then
          rspamd_logger.errx(task, "VEXIM: Authentication failed (401) for QID=%s", qid)
        elseif code == 403 then
          rspamd_logger.errx(task, "VEXIM: Forbidden (403) - check token ability 'rspamd:meta' for QID=%s", qid)
        elseif code == 400 then
          rspamd_logger.errx(task, "VEXIM: Bad request (400) for QID=%s", qid)
        elseif code ~= 200 then
          rspamd_logger.warnx(task, "VEXIM: Unexpected response code %s for QID=%s", code, qid)
        else
          rspamd_logger.infox(task, "VEXIM: Success for QID=%s", qid)
        end
      end
    })
  end
})
