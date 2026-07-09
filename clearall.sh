php artisan view:clear
php artisan view:cache
rm -rf storage/framework/views/*
php artisan optimize:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan filament:clear-cached-components
