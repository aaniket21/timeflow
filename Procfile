web: php artisan octane:start --server=swoole --host=0.0.0.0 --port=${PORT:-8000} --workers=auto --task-workers=auto
worker: php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
release: php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache
