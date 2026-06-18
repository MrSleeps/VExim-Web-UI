<?xml version="1.0" encoding="UTF-8"?>
<Autodiscover xmlns="http://schemas.microsoft.com/exchange/autodiscover/responseschema/2006">
    <Response xmlns="http://schemas.microsoft.com/exchange/autodiscover/outlook/responseschema/2006a">
        <User>
            <DisplayName>{{ $email }}</DisplayName>
        </User>
        <Account>
            <AccountType>email</AccountType>
            <Action>settings</Action>
            <Protocol>
                <Type>IMAP</Type>
                <Server>{{ $imapServer }}</Server>
                <Port>993</Port>
                <LoginName>{{ $email }}</LoginName>
                <DomainRequired>no</DomainRequired>
                <Authentication>password-cleartext</Authentication>
                <SSL>yes</SSL>
            </Protocol>
            <Protocol>
                <Type>SMTP</Type>
                <Server>{{ $smtpServer }}</Server>
                <Port>465</Port>
                <LoginName>{{ $email }}</LoginName>
                <DomainRequired>no</DomainRequired>
                <Authentication>password-cleartext</Authentication>
                <SSL>yes</SSL>
                <UsePOPAuth>no</UsePOPAuth>
            </Protocol>
        </Account>
    </Response>
</Autodiscover>