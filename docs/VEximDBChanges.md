users:

Added created_at & updated_at (if missing).

Added two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at, app_authentication_secret.

Added recovery_email.

Added remember_token (if missing).

Added on_whitelist.

users_web:

Added app_authentication_secret.

Added recovery_email.

Added active & deactivated_at.

Added max_domains, max_alias_domains, max_accounts, max_alias_accounts, max_quota.

whitelist_senders:

Added user_id column with foreign key to users.user_id.

Added a foreign key constraint on domain_id to the domains table.

domains:

Added whitelists boolean column.

domainalias:

Added created_at & updated_at timestamps.

groups:

Changed is_public column from char(1) to tinyInteger.