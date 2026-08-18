-- @post-schema
-- High-Performance Composite & Covering Indexes for Enterprise query acceleration
ALTER TABLE `news` ADD INDEX `idx_news_status_del_pub_id` (`status`, `deleted_at`, `published_at`, `id`);
ALTER TABLE `news_translations` ADD INDEX `idx_news_trans_composite` (`news_id`, `lang`);
ALTER TABLE `pages` ADD INDEX `idx_pages_entity_status_del` (`entity_type`, `status`, `deleted_at`, `is_home`);
ALTER TABLE `page_translations` ADD INDEX `idx_page_trans_composite` (`page_id`, `lang`);
ALTER TABLE `audit_logs` ADD INDEX `idx_audit_created_user` (`created_at`, `user_id`);
ALTER TABLE `jobs` ADD INDEX `idx_jobs_queue_status_avail` (`status`, `available_at`, `priority`, `id`);
