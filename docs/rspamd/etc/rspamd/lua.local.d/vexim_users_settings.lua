local rspamd_http = require "rspamd_http"
local rspamd_logger = require "rspamd_logger"
local ucl = require "ucl"
local N = "VEXIM_SETTINGS"

-- Read module config at load time
local module_cfg = rspamd_config:get_all_opt("vexim") or {}

local _api_host = module_cfg.api_url or ""
local cfg = {
    api_url = _api_host ~= "" and (_api_host:gsub("/$", "") .. "/api/v1/rspamd/settings") or "",
    api_key = module_cfg.api_key or "",
    timeout = module_cfg.timeout or 2,
    enabled = module_cfg.enabled ~= false,
    skip_authenticated = module_cfg.skip_authenticated ~= false  -- Default to true
}

-- Apply thresholds to the current task
local function apply_user_thresholds(task, actions, rcpt_email)
    if not actions then
        return false
    end

    local modified = false
    local actions_to_set = {}

    if actions.add_header then
        local val = tonumber(actions.add_header)
        if val then
            rspamd_logger.infox(task, "%s: Setting add_header=%.1f for %s", N, val, rcpt_email)
            actions_to_set['add header'] = val
            modified = true
        end
    end

    if actions.reject then
        local val = tonumber(actions.reject)
        if val then
            rspamd_logger.infox(task, "%s: Setting reject=%.1f for %s", N, val, rcpt_email)
            actions_to_set['reject'] = val
            modified = true
        end
    end

    if modified then
        task:set_settings({actions = actions_to_set})
    end

    return modified
end

-- Parse UCL response and extract actions
local function parse_response(task, body, rcpt_email)
    if not body or body == "" then
        rspamd_logger.debugx(task, "%s: Empty response body for %s", N, rcpt_email)
        return nil
    end

    local parser = ucl.parser()
    local res, err = parser:parse_string(body)

    if not res then
        rspamd_logger.debugx(task, "%s: Parse error for %s: %s", N, rcpt_email, err or "unknown")
        return nil
    end

    local parsed = parser:get_object()
    if not parsed then
        return nil
    end

    local actions = nil

    if parsed[rcpt_email] and parsed[rcpt_email].actions then
        actions = parsed[rcpt_email].actions
    elseif parsed.actions then
        actions = parsed.actions
    else
        for key, value in pairs(parsed) do
            if type(value) == "table" and value.actions then
                actions = value.actions
                break
            end
        end
    end

    return actions
end

-- Fetch user settings from Laravel API
local function fetch_user_settings(task, rcpt_email)
    local encoded_email = rcpt_email:gsub("@", "%%40")
    local url = cfg.api_url .. "/" .. encoded_email

    rspamd_logger.debugx(task, "%s: Requesting URL: %s", N, url)

    rspamd_http.request({
        task = task,
        url = url,
        method = "GET",
        headers = {
            ["Authorization"] = "Bearer " .. cfg.api_key,
            ["Accept"] = "application/json",
            ["User-Agent"] = "Rspamd-Vexim/1.0"
        },
        timeout = cfg.timeout,
        callback = function(err, code, body)
            if err then
                rspamd_logger.errx(task, "%s: HTTP error for %s: %s", N, rcpt_email, err)
                return
            end

            if code == 200 then
                rspamd_logger.debugx(task, "%s: Got 200 response for %s", N, rcpt_email)
                local actions = parse_response(task, body, rcpt_email)
                if actions then
                    apply_user_thresholds(task, actions, rcpt_email)
                else
                    rspamd_logger.debugx(task, "%s: No actions found for %s", N, rcpt_email)
                end
            elseif code == 404 then
                rspamd_logger.debugx(task, "%s: No custom settings for %s", N, rcpt_email)
            else
                rspamd_logger.errx(task, "%s: API returned %d for %s", N, code or 0, rcpt_email)
            end
        end
    })
end

-- Main callback function
local function vexim_settings_callback(task)
    if not cfg.enabled then
        return
    end

    if cfg.api_url == "" or cfg.api_key == "" then
        rspamd_logger.warnx(task, "%s: API URL or API key not configured", N)
        return
    end

    -- ============================================
    -- SKIP FOR AUTHENTICATED USERS
    -- ============================================
    -- Check if the sender is authenticated
    local username = task:get_user()
    if cfg.skip_authenticated and username and username ~= "" then
        rspamd_logger.debugx(task, "%s: Skipping for authenticated user: %s", N, username)
        return
    end
    
    -- Also skip for local networks (optional - configurable)
    local skip_local = module_cfg.skip_local or true
    if skip_local and task:has_flag("local") then
        rspamd_logger.debugx(task, "%s: Skipping for local connection", N)
        return
    end
    -- ============================================

    local recipients = task:get_recipients()
    if not recipients or #recipients == 0 then
        return
    end

    for _, rcpt in ipairs(recipients) do
        local rcpt_addr = rcpt['addr']
        if rcpt_addr then
            fetch_user_settings(task, rcpt_addr)
        end
    end
end

-- Register the symbol
rspamd_config:register_symbol({
    name = "VEXIM_USER_SETTINGS",
    type = "normal",
    callback = vexim_settings_callback,
    priority = 10,
    flags = {"empty"}
})

rspamd_logger.infox(rspamd_config, "%s module loaded successfully", N)
