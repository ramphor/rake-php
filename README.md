Rake PHP
=

The spider/crawler framework written in PHP

# Example code

```
$rake  = new Rake( 'rake_id', new Your_DB_Driver(), new Your_HTTP_Client() );
$rake->registerHtmlParser(function() {
  return new HTML_Document_Parser();
});

$tooth = new Your_Tooth_Class( $rake, 'cp_products' );
$feed  = new Sitemap( $tooth, 'site_map_url' );

$tooth->registerProcessor( new Your_Processor() );
$tooth->setFormat( Tooth::FORMAT_HTML );
$tooth->addFeed( $feed );

$rake->addTooth( $tooth );
$rake->execute();
```
