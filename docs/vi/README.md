# Rake 2.0 – Modular, Extensible, Event-driven Crawling & Data Migration Framework for PHP
---

Rake 2.0 là framework crawl, migrate, và đồng bộ dữ liệu đa nền tảng, được thiết kế theo kiến trúc plugin hóa, event-driven, và áp dụng các design pattern hiện đại nhất (Factory, Builder, Adapter, Command, State, Decorator, Chain of Responsibility, Observer/Event Bus).

- **Cấu trúc đa Tooth, đa Feed:** Mỗi project (Tooth) có thể khai báo nhiều nguồn dữ liệu (Feed) với các loại data khác nhau (URL, API, Sitemap, CSV, JSON, ...).
- **Resource-centric:** Mọi dữ liệu (product, post, project, category, image, ...) đều được chuẩn hóa thành resource, dễ quản lý, tái sử dụng, đồng bộ và export.
- **Manager/Registry:** Tất cả các thành phần chính (Reception, Parser, Feed Item Builder, Processor, HTTP Client, Database Driver, ...) đều có manager trung tâm, cho phép đăng ký, override, mở rộng dễ dàng.
- **Adapter Pattern:** Hỗ trợ nhiều HTTP client (Guzzle, Selenium, Webdriver, Unirest, ...) và database driver (wpdb, Eloquent, Doctrine, ...), dễ dàng tích hợp hoặc thay thế.
- **Command Pattern:** Mọi tác vụ (insert, update, download, export, ...) đều là command object, giúp queue/worker dễ retry, rollback, audit.
- **State Pattern:** Quản lý trạng thái phức tạp cho Feed Item, Resource, Tooth (pending, processing, done, error, ...).
- **Decorator Pattern:** Dễ dàng thêm log, validate, cache, ... cho processor/feed item mà không ảnh hưởng core logic.
- **Chain of Responsibility:** Xử lý tuần tự các bước (insert DB, export EPUB/JSON/DOCX/CSV, ...), dễ mở rộng, thay đổi thứ tự, thêm/bớt processor.
- **Event-driven (Observer/Event Bus):** Dễ tích hợp log, notify, cache, audit, ... mà không cần sửa code core.
- **Tích hợp công nghệ hiện đại:**
  - **symfony/dom-crawler** cho DOM parsing mạnh mẽ, chuẩn hóa.
  - **nesbot/carbon** cho xử lý ngày tháng thông minh, đa ngôn ngữ.
- **Batch mode, CLI-first:** Tối ưu cho automation, cronjob, CI/CD, log chi tiết, dễ kiểm soát trạng thái, resume, retry.
- **GUI mapping rule (tùy chọn):** Hỗ trợ inspect DOM, chỉnh sửa mapping rule trực quan, test thử rule, export/import rule dễ dàng.

**Rake 2.0** là lựa chọn lý tưởng cho các dự án crawl, migrate, đồng bộ dữ liệu lớn, đa nguồn, đa nền tảng, cần khả năng mở rộng, plugin hóa, và kiểm soát quy trình chuyên nghiệp.