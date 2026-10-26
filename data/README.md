# RiTiVerse Data Directory

> [!NOTE]
> As per architectural requirements, static `.json` files in this directory are **deprecated and disabled**.

All dynamic application data:
1. **Business Settings & CMS**: Loaded from the database (`settings` table) and cached via `\App\Core\Cache` (`ritiverse_business_data`, TTL 3600s).
2. **Menu Categories & Items**: Loaded from the database (`menu_categories`, `menu_items`, `item_variants`, `item_addons`) and cached via `\App\Core\Cache` (`ritiverse_menu_data`, TTL 3600s).
3. **Visual Gallery**: Loaded from the database (`gallery_items`) and cached via `\App\Core\Cache` (`ritiverse_gallery_data`, TTL 3600s).
4. **Customer Reviews**: Moderated reviews loaded from the database (`reviews`) and cached via `\App\Core\Cache` (`ritiverse_reviews_data`, TTL 1800s).

Managed through `\App\Services\AppDataService`.
Cache files are stored in `storage/cache/` and automatically invalidated upon CMS/Admin updates.
