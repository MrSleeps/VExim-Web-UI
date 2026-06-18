require ["vnd.dovecot.pipe", "copy", "imapsieve", "environment"];
if environment "imap.mailbox" "Trash" {
   stop;
}
pipe :copy "rspamc" ["learn_ham"];
