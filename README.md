Rake PHP
=

The spider/crawler framework written in PHP

# Example code

```
$rake  = new Rake( 'rake_id', new Your_DB_Driver(), new Your_HTTP_Client() );
$tooth = new Your_Tooth_Class( 'cp_products', $rake );
$feed  = new Sitemap( 'site_map_url' );
$feed->setTooth( $tooth );

$tooth->registerProcessor( new Your_Processor() );
$tooth->setFormat( Tooth::FORMAT_HTML );
$tooth->addFeed( $feed );

$rake->addTooth( $tooth );
$rake->execute();
```
