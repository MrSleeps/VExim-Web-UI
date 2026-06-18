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

-- Main symbol that replaces ALL multimap rules
rspamd_config:register_symbol({
    name = "VEXIM_FILTER",
    score = 0,
    group = "vexim",
    callback = function(task)
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
            sender_domain = sender and sender:match("@(.+)$"),
            ip = ip_addr,
            recipient = recipient,
            recipient_domain = recipient:match("@(.+)$"),
            subject = subject
        }

        rspamd_logger.infox(task, "VEXIM_FILTER: calling API")

        -- Make API call
        rspamd_logger.infox(task, "VEXIM_FILTER: Using API_KEY starting with: %s", API_KEY:sub(1, 10))
        rspamd_http.request({
            url = API_URL,
            method = "POST",
            body = ucl.to_format(payload, "json"),
            headers = {
                ["Authorization"] = "Bearer " .. API_KEY,
                ["Content-Type"] = "application/json",
            },
            timeout = 3.0,
            task = task,
            callback = function(err, code, body)
                if err then
                    rspamd_logger.errx(task, "VEXIM_FILTER: HTTP error: %s", err)
                    task:insert_result("VEXIM_API_ERROR", 0.0, "api_unavailable")
                    return
                end

                if code ~= 200 then
                    rspamd_logger.errx(task, "VEXIM_FILTER: API returned %d: %s", code, body or "")
                    return
                end

                if not body then
                    rspamd_logger.errx(task, "VEXIM_FILTER: No response body from API")
                    return
                end

                rspamd_logger.infox(task, "VEXIM_FILTER: API response received")

                local parser = ucl.parser()
                local ok, parse_err = parser:parse_string(body)
                if not ok then
                    rspamd_logger.errx(task, "VEXIM_FILTER: Failed to parse API response: %s | body: %s", parse_err, body)
                    return
                end
				
                local result = parser:get_object()
                if not result then
                    rspamd_logger.errx(task, "VEXIM_FILTER: Empty result after parsing")
                    return
                end
				
                -- Whitelist check (highest priority - skip everything)
                if result.whitelist == true then
                    task:insert_result("VEXIM_WHITELIST", 1.0, sender)
                    task:set_pre_result("no action", "Whitelisted sender", "vexim")
                    rspamd_logger.infox(task, "VEXIM_FILTER: ✓ WHITELIST hit for %s", sender)
                    return
                end

                -- Global blocklist
                if result.global_blocklist == true then
                    task:insert_result("VEXIM_GLOBAL_BLOCK", 15.0, sender)
                    if result.action == "reject" then
                        task:set_pre_result("reject", "IP/domain blocked by global policy")
                    end
                    rspamd_logger.infox(task, "VEXIM_FILTER: ✗ GLOBAL BLOCK for %s", sender)
                    return
                end

                -- User blocklist with severity colors
                if result.blocklist == true then
                    local color = result.color or "yellow"
                    local matched_rule = result.matched_rule or "sender"

                    if color == "red" then
                        task:insert_result("VEXIM_BLOCK_RED", 15.0, sender)
                        task:set_pre_result("reject", string.format("Blocked sender (%s)", matched_rule))
                        rspamd_logger.infox(task, "VEXIM_FILTER: ✗ RED BLOCK for %s (rule: %s)", sender, matched_rule)

                    elseif color == "yellow" then
                        task:insert_result("VEXIM_BLOCK_YELLOW", 8.0, sender)
                        rspamd_logger.infox(task, "VEXIM_FILTER: ⚠ YELLOW BLOCK for %s, score increased", sender)
                    else
                        task:insert_result("VEXIM_BLOCK", result.score or 12.0, sender)
                    end
                    return
                end

                -- Subject blocklist
                if result.subject_blocked == true then
                    task:insert_result("VEXIM_SUBJECT_BLOCK", result.subject_score or 8.0, subject)
                    rspamd_logger.infox(task, "VEXIM_FILTER: ⚠ SUBJECT BLOCK: %s", subject)
                    return
                end

                rspamd_logger.infox(task, "VEXIM_FILTER: No block/whitelist rules matched")
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
    parent = "VEXIM_FILTER",
})
 
rspamd_config:register_symbol({
    name = "VEXIM_GLOBAL_BLOCK",
    score = 15.0,
    group = "vexim",
    type = "virtual",
    parent = "VEXIM_FILTER",
})
 
rspamd_config:register_symbol({
    name = "VEXIM_BLOCK_RED",
    score = 0.0,
    group = "vexim",
    type = "virtual",
    parent = "VEXIM_FILTER",
})
 
rspamd_config:register_symbol({
    name = "VEXIM_BLOCK_YELLOW",
    score = 8.0,
    group = "vexim",
    type = "virtual",
    parent = "VEXIM_FILTER",
})
 
rspamd_config:register_symbol({
    name = "VEXIM_BLOCK",
    score = 12.0,
    group = "vexim",
    type = "virtual",
    parent = "VEXIM_FILTER",
})
 
rspamd_config:register_symbol({
    name = "VEXIM_SUBJECT_BLOCK",
    score = 8.0,
    group = "vexim",
    type = "virtual",
    parent = "VEXIM_FILTER",
})

rspamd_config:register_symbol({
    name = "VEXIM_API_ERROR",
    score = 0.0,
    group = "vexim",
    type = "virtual",
    parent = "VEXIM_FILTER",
})
