# Magento Portfolio Store

A personal portfolio built on **Magento Open Source** where my skills are listed as products.  
When an order is placed, Magento emails the customer **and** sends me a custom notification (captured locally via MailHog).

## What’s inside

**Vendor namespace:** `KunalMagento`

- `KunalMagento_SkillCatalog`  
  Seed/demo “skills” as products via CLI, tidy attributes, and a simple catalog.
- `KunalMagento_PortfolioNotifications`  
  Sends a custom “Portfolio Order Notification to Me” email on order placement.

## Requirements (local dev)

- PHP **8.2+**, Composer 2
- MySQL 8+ (or MariaDB 10.6+)
- OpenSearch 2.x (or Elasticsearch 7.x) for Magento 2.4.x
- MailHog (for local email testing)
- Any web stack (Valet/NGINX/Apache). Document root should point to **`/pub`**

## Quickstart

```bash
# 1) Install dependencies
composer install

# 2) Create an empty database first, then install Magento (adjust creds/URLs)
bin/magento setup:install \
  --base-url="https://magento-ce.dev/" \
  --db-host="127.0.0.1" --db-name="magento_ce" --db-user="root" --db-password="" \
  --backend-frontname="admin" \
  --admin-firstname="Kunal" --admin-lastname="Upadhyay" \
  --admin-email="admin@example.local" --admin-user="admin" --admin-password="Admin@12345" \
  --language="en_US" --currency="USD" --timezone="Asia/Kolkata" \
  --use-rewrites=1

# (If you already have app/etc/env.php + DB, just do:)
# bin/magento setup:upgrade

# 3) Developer mode + static assets
bin/magento deploy:mode:
