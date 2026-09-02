#!/bin/sh

set -e

php artisan optimize

exec "$@"