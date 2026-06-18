## Installing RSpamD

This guide is for Debian Trixie and is mostly lifted from the RSpamD website.

Login to your shell using an account that has sudo permissions.

Run these commands:

```
# Install prerequisites
sudo apt update
sudo apt install -y lsb-release wget gpg
sudo apt install redis

# Add GPG key
sudo mkdir -p /etc/apt/keyrings
wget -O- https://rspamd.com/apt-stable/gpg.key | gpg --dearmor | sudo tee /etc/apt/keyrings/rspamd.gpg > /dev/null

# Add repository
CODENAME=$(lsb_release -c -s)
echo "deb [signed-by=/etc/apt/keyrings/rspamd.gpg] http://rspamd.com/apt-stable/ $CODENAME main" | sudo tee /etc/apt/sources.list.d/rspamd.list
echo "deb-src [signed-by=/etc/apt/keyrings/rspamd.gpg] http://rspamd.com/apt-stable/ $CODENAME main" | sudo tee -a /etc/apt/sources.list.d/rspamd.list

# Install
sudo apt update
sudo apt install rspamd
```
Now you need to add the config files located in docs/rspamd

In local.d you have the following:

**antivirus.conf** - this is an example using Clamav
**classifier-bayes.conf** -  this is what makes RSpamd learn, you may need to tweak the last 2 lines depending on your prefences.
**groups.conf** - This tells RSpamd what to score virus mails (leave it high so it auto-rejects)
**redis.conf** - the ip address of your redis server
**worker-controller.inc** - settings for rspamd

In the lua.local.d folder:
**vexim_metadata_exporter.lua** - passes data for the rspamd stats in the ui
**vexim_users_settings.lua**  - gets the users tag/reject scores
**vexim_whitelist_blocklist.lua** - checks the users lists

Finally, modules.local.d:
**vexim.conf** - settings that are used by the lua scripts. 

The vexim.conf is the file that stores the api key (more on that in a second) and the api URL.  You get your api key by going over the account that is hosting the VExim web ui.
```
cd vexim_web

# Find your system admin user id
php artisan vw:users list --role=system_admin

# Create a token using that id
php artisan vw:create-rspamd-token user_id
```
Take a note of that token (the full token will look something like 1|alongstring)

`nano /etc/rspamd/modules.local.d/vexim.conf`

and paste your token into the api_key section (don't forget to enclose it in ""), you will also need to add api_url, this is just the host of your VExim web ui ie http://vexim.your.domain/ (again enclose it in "").

Restart RSpamd and it should all work, not working? Have a look at /var/log/rspamd/rspamd.log or your syslog, it should tell you what is going on.
