#!/bin/bash

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Error handling function
error_exit() {
    echo -e "${RED}ERROR: $1${NC}" >&2
    exit 1
}

# Create necessary directories
create_directories() {
    echo -e "${YELLOW}Creating required directories...${NC}"
    
    # Create bootstrap/cache directory
    mkdir -p bootstrap/cache
    chmod 775 bootstrap/cache
    
    # Create storage directories
    mkdir -p storage/framework/cache
    mkdir -p storage/framework/sessions
    mkdir -p storage/framework/views
    mkdir -p storage/logs
    mkdir -p storage/app
    
    # Set permissions
    chmod -R 775 storage
    chmod -R 775 bootstrap/cache
    
    echo -e "${GREEN}Directories created and permissions set${NC}"
}

# Function for fresh setup (install vexim database tables)
install_vexim_tables() {
    echo -e "${YELLOW}Installing Vexim database tables...${NC}"
    php artisan migrate --path=database/vexim-migrations --force
}

# Function to prompt for domain details
prompt_for_domain() {
    echo -e "${YELLOW}========================================${NC}"
    echo -e "${YELLOW}Default Domain Configuration${NC}"
    echo -e "${YELLOW}========================================${NC}"
    echo
    
    # Domain name
    while true; do
        read -p "Enter domain name (e.g., example.com): " DOMAIN_NAME
        if [[ -z "$DOMAIN_NAME" ]]; then
            echo -e "${RED}Domain name cannot be empty${NC}"
        elif [[ ! "$DOMAIN_NAME" =~ ^([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$ ]]; then
            echo -e "${RED}Invalid domain format. Please enter a valid domain (e.g., example.com)${NC}"
        else
            break
        fi
    done
    
    # SpamAssassin
    echo
    read -p "Enable SpamAssassin? (y/n) [n]: " ENABLE_SPAMASSASSIN
    if [[ "$ENABLE_SPAMASSASSIN" =~ ^[Yy]$ ]]; then
        SPAMASSASSIN="--spamassassin"
        
        # SA Tag score
        read -p "SpamAssassin tag score [2]: " SA_TAG
        SA_TAG=${SA_TAG:-2}
        
        # SA Refuse score
        read -p "SpamAssassin refuse score [5]: " SA_REFUSE
        SA_REFUSE=${SA_REFUSE:-5}
    else
        SPAMASSASSIN=""
        SA_TAG="2"
        SA_REFUSE="5"
    fi
    
    # AV Scan
    echo
    read -p "Enable virus scanning? (y/n) [n]: " ENABLE_AVSCAN
    if [[ "$ENABLE_AVSCAN" =~ ^[Yy]$ ]]; then
        AVSCAN="--avscan"
    else
        AVSCAN=""
    fi
    
    # Quotas
    echo
    read -p "Domain quota in MB [0 (unlimited)]: " QUOTAS
    QUOTAS=${QUOTAS:-0}
    
    # Max accounts
    echo
    read -p "Maximum number of email accounts [100]: " MAX_ACCOUNTS
    MAX_ACCOUNTS=${MAX_ACCOUNTS:-100}
    
    echo
    echo -e "${GREEN}Domain configuration summary:${NC}"
    echo -e "  Domain: ${GREEN}${DOMAIN_NAME}${NC}"
    echo -e "  Type: ${GREEN}local${NC}"
    echo -e "  SpamAssassin: ${GREEN}$([[ "$SPAMASSASSIN" != "" ]] && echo "Enabled" || echo "Disabled")${NC}"
    if [[ "$SPAMASSASSIN" != "" ]]; then
        echo -e "    - Tag score: ${GREEN}${SA_TAG}${NC}"
        echo -e "    - Refuse score: ${GREEN}${SA_REFUSE}${NC}"
    fi
    echo -e "  Virus Scanning: ${GREEN}$([[ "$AVSCAN" != "" ]] && echo "Enabled" || echo "Disabled")${NC}"
    echo -e "  Quota: ${GREEN}${QUOTAS} MB$([[ ${QUOTAS} -eq 0 ]] && echo " (unlimited)" || echo "")${NC}"
    echo -e "  Max Accounts: ${GREEN}${MAX_ACCOUNTS}${NC}"
    
    echo
    read -p "Proceed with this configuration? (y/n) [y]: " CONFIRM
    if [[ "$CONFIRM" =~ ^[Nn]$ ]]; then
        echo -e "${YELLOW}Restarting domain configuration...${NC}"
        prompt_for_domain
    fi
}

# Function to add the default domain
add_default_domain() {
    echo -e "${YELLOW}Adding default domain...${NC}"
    
    # Build the command
    CMD="php artisan vw:add-domain ${DOMAIN_NAME} \
        --type=local \
        --max-accounts=${MAX_ACCOUNTS} \
        --quotas=${QUOTAS} \
        --sa-tag=${SA_TAG} \
        --sa-refuse=${SA_REFUSE}"
    
    # Add optional flags
    if [[ "$SPAMASSASSIN" != "" ]]; then
        CMD="${CMD} --spamassassin"
    fi
    
    if [[ "$AVSCAN" != "" ]]; then
        CMD="${CMD} --avscan"
    fi
    
    # Always enable the domain by default
    CMD="${CMD} --enabled"
    
    # Execute the command
    echo -e "${YELLOW}Executing: ${CMD}${NC}"
    if eval $CMD; then
        echo -e "${GREEN}Default domain '${DOMAIN_NAME}' added successfully${NC}"
    else
        echo -e "${RED}Failed to add default domain${NC}"
        read -p "Continue anyway? (y/n) [n]: " CONTINUE
        if [[ ! "$CONTINUE" =~ ^[Yy]$ ]]; then
            error_exit "Domain creation failed. Exiting."
        fi
    fi
}

# Function for main setup
main_setup() {
    echo -e "${GREEN}Running main setup...${NC}"
    echo -e "${GREEN}Generating app key${NC}"
    php artisan key:generate
    echo -e "${GREEN}Creating web database tables${NC}"
    php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="migrations"
    php artisan migrate
    echo -e "${GREEN}Seeding new tables${NC}"
    php artisan db:seed --class=RolesAndPermissionsSeeder
    php artisan db:seed --class=SettingsSeeder
    php artisan db:seed --class="FinityLabs\\FinMail\\Database\\Seeders\\EmailTemplateSeeder"
    php artisan vw:create-sysadmin
    
    # Add default domain after seeding
    add_default_domain
    
    npm run build
    echo -e "${GREEN}Main setup completed${NC}"
}

# Validation function
validate_env() {
    # Check if .env exists
    if [ ! -f ".env" ]; then
        error_exit ".env file does not exist in current directory"
    fi
    
    # Source the .env file
    set -a
    source .env
    set +a
    
    # List of required variables
    required_vars=(
        "DB_CONNECTION"
        "DB_HOST"
        "DB_PORT"
        "DB_DATABASE"
        "DB_USERNAME"
        "DB_PASSWORD"
        "REDIS_CLIENT"
        "REDIS_HOST"
        "REDIS_PASSWORD"
        "REDIS_PORT"
        "MAIL_MAILER"
        "MAIL_HOST"
        "MAIL_PORT"
        "MAIL_USERNAME"
        "MAIL_PASSWORD"
        "MAIL_FROM_ADDRESS"
        "MAIL_FROM_NAME"
        "MAIL_FROM_SUPPORT_ADDRESS"
        "MAIL_FROM_SUPPORT_NAME"
        "VEXIM_UID"
        "VEXIM_GID"
    )
    
    # Track missing variables
    missing_vars=()
    
    # Check each variable
    for var in "${required_vars[@]}"; do
        value="${!var}"
        if [ -z "${value}" ]; then
            missing_vars+=("$var")
        fi
    done
    
    # If any variables are missing, show error and exit
    if [ ${#missing_vars[@]} -ne 0 ]; then
        echo -e "${RED}ERROR: The following required variables are missing or empty in .env:${NC}" >&2
        for var in "${missing_vars[@]}"; do
            echo -e "${RED}  - $var${NC}" >&2
        done
        exit 1
    fi
    
    # Set MAIL_MAILER to smtp if needed
    if [ "$MAIL_MAILER" != "smtp" ]; then
        echo -e "${GREEN}Setting MAIL_MAILER to smtp...${NC}"
        if grep -q "^MAIL_MAILER=" .env; then
            sed -i 's/^MAIL_MAILER=.*/MAIL_MAILER=smtp/' .env
        else
            echo "MAIL_MAILER=smtp" >> .env
        fi
        # Re-source to get the updated value
        source .env
    fi
    
    echo -e "${GREEN}Environment validation passed${NC}"
    return 0
}

# Ask the fresh setup question
ask_fresh_setup() {
    echo
    echo -e "${YELLOW}Is this a fresh setup? Do you want to install the main Vexim database tables?${NC}"
    while true; do
        read -p "Enter y or n: " -n 1 -r
        echo
        case $REPLY in
            [Yy]*)
                echo -e "${GREEN}Installing Vexim database tables...${NC}"
                install_vexim_tables
                break
                ;;
            [Nn]*)
                echo -e "${GREEN}Skipping Vexim database tables installation.${NC}"
                break
                ;;
            *)
                echo -e "${RED}Please answer y or n${NC}"
                ;;
        esac
    done
    echo
}

# Function to update .env for production
update_production_env() {
    echo -e "${YELLOW}Updating .env for production...${NC}"
    
    # Change APP_ENV from local to production
    if grep -q "^APP_ENV=" .env; then
        sed -i 's/^APP_ENV=.*/APP_ENV=production/' .env
        echo -e "${GREEN}APP_ENV set to production${NC}"
    else
        echo "APP_ENV=production" >> .env
        echo -e "${GREEN}APP_ENV added as production${NC}"
    fi
    
    # Change APP_DEBUG from true to false
    if grep -q "^APP_DEBUG=" .env; then
        sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env
        echo -e "${GREEN}APP_DEBUG set to false${NC}"
    else
        echo "APP_DEBUG=false" >> .env
        echo -e "${GREEN}APP_DEBUG added as false${NC}"
    fi
    
    echo -e "${GREEN}Production environment settings applied${NC}"
}

# Main execution flow
main() {
    # Step 1: Create necessary directories FIRST
    create_directories
    
    # Step 2: Validate environment
    validate_env
    
    # Step 3: Composer Install
    composer install
    
    # Step 4: npm install
    npm install
    
    # Step 5: Ask about fresh setup / Vexim tables
    ask_fresh_setup
    
    # Step 6: Prompt for domain configuration
    prompt_for_domain
    
    # Step 7: Run main setup (includes adding the domain)
    main_setup
    
    # Step 8: Convert .env to production
    update_production_env
    
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}Setup completed successfully!${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}Default domain: ${DOMAIN_NAME}${NC}"
    echo -e "${YELLOW}You can add more domains later using:${NC}"
    echo -e "${YELLOW}  php artisan vw:add-domain your-domain.com${NC}"
}

# Run the main function
main
