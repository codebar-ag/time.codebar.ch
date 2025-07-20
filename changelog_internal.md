# Changelog

## 20250720 

### Updated database.php to accept read/write hosts

````
'read' => [
    'host' => env('DB_READ_HOST', env('DB_HOST')),
],
'write' => [
    'host' => env('DB_WRITE_HOST', env('DB_HOST')),
],
// 'host' => env('DB_HOST', '127.0.0.1'),
`
````

### Early Returned Clients & Project Delete API Endpoint

With this response, the request always returns successfully, but the projects and clients are not being deleted.
