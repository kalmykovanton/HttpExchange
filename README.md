# HttpExchange
ItCourses Framework HttpExchange Component
## The most useful methods for working with HTTP requests.
NOTE: In the development of this component, namely the implementation of the 'PSR-7: HTTP message interfaces' recommendations, there were moments when the inspiration left me, I lost the thread and the realization came to a standstill. In such situations, I have found strength and drew new ideas for this project from the developers of the Zend framework, for which many thanks to these guys. Also many thanks to one of the PSR-7 developers Matthew Weier O'Finney, for the wonderful articles, that helped me become uproarious what's what. See [PSR-7: HTTP message interfaces](http://www.php-fig.org/psr/psr-7/), [Zend-Diactoros](https://github.com/zendframework/zend-diactoros), [PSR-7 By Example](https://mwop.net/blog/2015-01-26-psr-7-by-example.html), [On PSR7 and HTTP Headers](https://mwop.net/blog/2015-07-28-on-psr7-headers.html), [PSR-7 And Middleware](http://weierophinney.github.io/2015-10-20-PSR-7-and-Middleware/#/). This vision of the PSR-7 does not apply for full implementation of recommendations, since, maybe something was not completely realized or lost, and some things have been adapted to our personal project, so сomments and criticism welcome. PSR-7 - it was not easy, but it works :+1:

Create new request instance:
```php
use App\Http\Request\Request;
$request = new Request();

// or without namespaces
$request = new \App\Http\Request\Request();
```
To retrieving array of parameters from superglobal $_SERVER:
```php
$serverParams = $request->getServerParams(); // return array if present or empty array 
```
Or if you want to get a specific parameter use:
```php
$specParam = $request->getFromServer($value) // return string if present or empty string
```
To retrieving array of cookies from superglobal $_COOKIE:
```php
$cookie = $request->getCookieParams(); // return array if present or empty array
```
Get query parameters (typically from $_GET):
```php 
$queryParams = $request->getQueryParams(); // return array if present or empty array
```
Retrieve any parameters provided in the request body (e.g. from $_POST or JSON):
```php
$body = $request->getParsedBody(); // array|object deserialized body parameters or null if absent.
```
To retrieve request, in most cases, this will be the origin-form of the composed URI like "/some/acrion?name='John'&age='22'" or just "/some/acrion/name/22" if no query:
```php
$target = $request->getRequestTarget(); // string or '/' if absent.
```
To retrieves the HTTP method of the request use:
```php
$method = $request->getMethod(); // HTTP method or empty string if absent.
```
To retrieves the HTTP replaced method of the request, usually, by specifying attributes name="_method" and value="PUT" (or else valid method) at html form 'input' fileds:
```php
$replacedMethod = $request->getReplacedMethod(); // Returns the replaced request method or empty string if absent.
```
If you want to check whether the data transferred via the Ajax, use:
```php
$request->isAjax(); // if Ajax request return true, else false.
```
To access all user input data, like query params or parsed body params (POST, json or other). You may pass a default value as the second argument. This value will be returned if the requested input value is not present.
```php
$input = $request->input(string $name [, string $default = null ]) // parameter, default parameter or empty string
```
### Working with uploded files.
```html
<!-- HTML -->
<input type="file" name="my-form[details][avatar]" />
```
```php
// PHP
$file = $request->getUploadedFiles()['my-form']['details']['avatar']; // array tree of UploadedFileInterface instances or empty array

//retrieve the file size
$file->getSize(); // the file size in bytes or null if unknown.

// retrieve the error associated with the uploaded file.
$file->getError(); // one of PHP's UPLOAD_ERR_XXX constants.

// retrieve the filename sent by the client
$file->getClientFilename(); // the filename sent by the client or null if none.

// retrieve the media type sent by the client
$file->getClientMediaType(); // the media type sent by the client or null if none.

// move the uploaded file to a new location
$file->moveTo(/some/path/file.txt);

```

