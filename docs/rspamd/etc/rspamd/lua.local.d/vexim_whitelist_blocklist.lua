-- Whitelist/Blocklist engine for Vexim
-- Direct API calls without Redis caching (simplified)

local rspamd_http = require "rspamd_http"
local ucl = require "ucl"
local rspamd_logger = require "rspamd_logger"

-- Configuration
local opts = rspamd_config:get_all_opt("vexim")
local API_HOST = opts and opts.api_url or ""
local API_KEY = opts and opts.api_key or ""
local API_URL = API_HOST ~= "" and (API_HOST:gsub("/$", "") .. "/api/v1/rspamd/check") or ""
local SKIP_AUTHENTICATED = opts and opts.skip_authenticated ~= false  -- Default to true if not set
local SKIP_LOCAL = opts and opts.skip_local ~= false  -- Default to true if not set
local TIMEOUT = tonumber(opts and opts.timeout) or 2

-- Main symbol that replaces ALL multimap rules
local parent_id = rspamd_config:register_symbol({
    name = "VEXIM_FILTER",
    type = "callback",
    score = 0,
    group = "vexim",
    callback = function(task)
        if opts and opts.enabled == false then return end
        if API_URL == "" or API_KEY == "" then
            rspamd_logger.errx(task, "VEXIM_FILTER: missing API URL or API key")
            task:insert_result("VEXIM_API_ERROR", 1.0, "missing_configuration")
            return
        end
        -- Skip for authenticated users if configured
        if SKIP_AUTHENTICATED then
            local username = task:get_user()
            if username and username ~= "" then
                rspamd_logger.infox(task, "VEXIM_FILTER: Skipping for authenticated user: %s", username)
                return
            end
        end
    
        -- Skip for local connections if configured
        if SKIP_LOCAL and task:has_flag("local") then
            rspamd_logger.infox(task, "VEXIM_FILTER: Skipping for local connection")
            return
        end
        -- Extract email data
        local from = task:get_from(1)
        local sender = from and from[1] and from[1].addr or nil
        local mime_from = task:get_from("mime")
        local mime_sender = mime_from and mime_from[1] and mime_from[1].addr or nil

        local ip = task:get_ip()
        local ip_addr = ip and ip:to_string() or nil

        local rcpt = task:get_recipients(1)
        local recipient = rcpt and rcpt[1] and rcpt[1].addr or nil

        local subject = task:get_header("Subject") or ""

        -- Skip if no sender or recipient
        if not sender or not recipient then
            rspamd_logger.infox(task, "VEXIM_FILTER: skipping - no sender or recipient")
            return
        end

        rspamd_logger.infox(task, "VEXIM_FILTER: checking %s -> %s", sender, recipient)

        -- Payload for API call
        local payload = {
            sender = sender,
            mime_sender = mime_sender,
            -- DMARC pass validates alignment of the visible From domain.
            mime_sender_verified = task:has_symbol("DMARC_POLICY_ALLOW") == true,
            mime_sender_domain = mime_sender and mime_sender:match("@(.+)$"),
            sender_domain = sender and sender:match("@(.+)$"),
            ip = ip_addr,
            recipient = recipient,
            recipient_domain = recipient:match("@(.+)$"),
            subject = subject
        }

        rspamd_logger.infox(task, "VEXIM_FILTER: calling API")

        -- Make API call
        rspamd_http.request({
            url = API_URL,
            method = "POST",
            body = ucl.to_format(payload, "json"),
            headers = {
                ["Authorization"] = "Bearer " .. API_KEY,
                ["Content-Type"] = "application/json",
            },
            timeout = TIMEOUT,
            task = task,
            callback = function(err, code, body)
                if err then
                    rspamd_logger.errx(task, "VEXIM_FILTER: HTTP error: %s", err)
                    task:insert_result("VEXIM_API_ERROR", 1.0, "api_unavailable")
                    return
                end

                if code ~= 200 then
                    rspamd_logger.errx(task, "VEXIM_FILTER: API returned status %s", tostring(code))
                    task:insert_result("VEXIM_API_ERROR", 1.0, "http_status_" .. tostring(code))
                    return
                end

                if not body then
                    rspamd_logger.errx(task, "VEXIM_FILTER: No response body from API")
                    task:insert_result("VEXIM_API_ERROR", 1.0, "empty_response")
                    return
                end

                rspamd_logger.infox(task, "VEXIM_FILTER: API response received")

                local parser = ucl.parser()
                local ok, parse_err = parser:parse_string(body)
                if not ok then
                    rspamd_logger.errx(task, "VEXIM_FILTER: Failed to parse API response: %s", tostring(parse_err))
                    task:insert_result("VEXIM_API_ERROR", 1.0, "invalid_json")
                    return
                end
				
                local result = parser:get_object()
                if not result then
                    rspamd_logger.errx(task, "VEXIM_FILTER: Empty result after parsing")
                    task:insert_result("VEXIM_API_ERROR", 1.0, "empty_result")
                    return
                end
				
                -- Whitelist check (highest priority - skip everything)
                if result.whitelist == true then
                    task:insert_result("VEXIM_WHITELIST", 1.0, sender)
                    task:set_pre_result("no action", "Vexim whitelisted sender", "vexim")
                    rspamd_logger.infox(task, "VEXIM_FILTER: WHITELIST hit for %s", sender)
                    return
                end

                -- Global blocklist
                if result.global_blocklist == true then
                    task:insert_result("VEXIM_GLOBAL_BLOCK", 1.0, sender)
                    if result.action == "reject" then
                        task:set_pre_result("reject", "IP/domain blocked by global policy")
                    end
                    rspamd_logger.infox(task, "VEXIM_FILTER: GLOBAL BLOCK for %s", sender)
                    return
                end

                -- User blocklist with severity colors
                if result.blocklist == true then
                    local color = result.color or "yellow"
                    local matched_rule = result.matched_rule or "sender"

                    if color == "red" then
                        task:insert_result("VEXIM_BLOCK_RED", 1.0, sender)
                        task:set_pre_result("reject", string.format("Blocked sender (%s)", matched_rule))
                        rspamd_logger.infox(task, "VEXIM_FILTER: RED BLOCK for %s (rule: %s)", sender, matched_rule)

                    elseif color == "yellow" then
                        task:insert_result("VEXIM_BLOCK_YELLOW", 1.0, sender)
                        rspamd_logger.infox(task, "VEXIM_FILTER: YELLOW BLOCK for %s, score increased", sender)
                    else
                        task:insert_result("VEXIM_BLOCK", (tonumber(result.score) or 12.0) / 12.0, sender)
                    end
                    return
                end

                -- Subject blocklist
                if result.subject_blocked == true then
                    task:insert_result("VEXIM_SUBJECT_BLOCK", (tonumber(result.subject_score) or 8.0) / 8.0, subject)
                    rspamd_logger.infox(task, "VEXIM_FILTER: SUBJECT BLOCK: %s", subject)
                    return
                end

                rspamd_logger.infox(task, "VEXIM_FILTER: No block/whitelist rules matched (envelope=%s, mime=%s)", sender, mime_sender or "none")
            end
        })
    end
})

-- Register virtual symbols so rspamd knows about them
rspamd_config:register_symbol({
    name = "VEXIM_WHITELIST",
    score = -10.0,
    group = "vexim",
    type = "virtual",
    parent = parent_id,
    flags = "nice",
})
 
rspamd_config:register_symbol({
    name = "VEXIM_GLOBAL_BLOCK",
    score = 15.0,
    group = "vexim",
    type = "virtual",
    parent = parent_id,
})
 
rspamd_config:register_symbol({
    name = "VEXIM_BLOCK_RED",
    score = 15.0,
    group = "vexim",
    type = "virtual",
    parent = parent_id,
})
 
rspamd_config:register_symbol({
    name = "VEXIM_BLOCK_YELLOW",
    score = 8.0,
    group = "vexim",
    type = "virtual",
    parent = parent_id,
})
 
rspamd_config:register_symbol({
    name = "VEXIM_BLOCK",
    score = 12.0,
    group = "vexim",
    type = "virtual",
    parent = parent_id,
})
 
rspamd_config:register_symbol({
    name = "VEXIM_SUBJECT_BLOCK",
    score = 8.0,
    group = "vexim",
    type = "virtual",
    parent = parent_id,
})

rspamd_config:register_symbol({
    name = "VEXIM_API_ERROR",
    score = 0.0,
    group = "vexim",
    type = "virtual",
    parent = parent_id,
})

-- Vexim must observe DMARC verification before it can trust MIME From.
rspamd_config:register_dependency("VEXIM_FILTER", "DMARC_CHECK")
