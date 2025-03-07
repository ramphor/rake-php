Rake PHP
=

The spider/crawler framework written in PHP

# Example code

```
$rake  = new Rake( 'rake_id', new Your_DB_Driver(), new Your_HTTP_Client() );
$tooth = new Your_Tooth_Class( 'cp_products', $rake );
$feed  = new Sitemap( 'feed_id' );
$feed->setUrl('https://site_map_url.com');
$feed->setTooth( $tooth );

$tooth->registerProcessor( new Your_Processor() );
$tooth->setFormat( Tooth::FORMAT_HTML );
$tooth->addFeed( $feed );

$rake->addTooth( $tooth );
$rake->execute();
```


# Kiến trúc:
- Sử dụng mô hình Facade để truy cập các service
- Có các abstract class và interface định nghĩa các contract
- Sử dụng dependency injection
- Có khả năng mở rộng thông qua các class kế thừa

# Các thành phần chính:
- App: Singleton class quản lý các instance
- Rake: Class chính để thực hiện crawl
- Tooth: Abstract class định nghĩa cách xử lý dữ liệu
- Feed: Abstract class định nghĩa nguồn dữ liệu
- Resource: Quản lý tài nguyên (ảnh, file...)
- Parser: Xử lý dữ liệu thô thành FeedItem

![class-diagram](https://github.com/user-attachments/assets/c2a4f042-3692-41ab-8883-e8d455a1378a)


# Luồng xử lý:
- Khởi tạo Rake instance
- Thêm các Tooth để xử lý dữ liệu
- Tooth chứa Feed để lấy dữ liệu
- Parser chuyển đổi dữ liệu thành FeedItem
- Processor xử lý FeedItem
= Resource Manager quản lý tài nguyên

![Image](https://github.com/user-attachments/assets/0be071aa-100c-42f5-a715-56e87b6e85a4)

# Các tính năng:

- Hỗ trợ nhiều loại feed (CSV, HTML, Sitemap)
- Có khả năng download và xử lý tài nguyên
- Lưu trữ dữ liệu vào database
- Có hệ thống logging
- Có cơ chế retry khi lỗi


# Adapter to working with your CMS/Framework

- WordPress: https://github.com/puleeno/rake-wordpress-adapter
- Laravel: https://github.com/puleeno/rake-laravel-adapter
