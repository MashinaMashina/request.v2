# request.v2
Easy requests with CURL

## Начало работы
1. Подключить файл request/request.php
2. Можно работать :blush:

## Программа уровня Hello world
```php
$request = new request('https://github.com');
$request->send();

echo $request->dump();
```

Программа посылает GET запрос на адрес https://github.com и выводит отчет в HTML формате.

## Функции класса request
**\_\_construct($url) ** - принимает один параметр в виде URL.
**option($key, $value) **- устанавливает параметр для класса. Доступные параметры:
> convert_encoding - (bool) включает / отключает автоматическое преобразование ответа сервера в UTF-8

**set($key, $value)** - устанавливает параметр CURL. Список доступных параметров: http://php.net/manual/ru/function.curl-setopt.php .
**session($session)** - принимает в качестве аргумента объект класса request_session.
**send()** - отправляет запрос. Никаких параметров не принимает.
**post($data = array())** - позволяет передавать POST данные.
**payload($data = array())** - позволяет отправлять POST в виде JSON массива (https://stackoverflow.com/questions/23118249/whats-the-difference-between-request-payload-vs-form-data-as-seen-in-chrome).
**error()** - возвращает текст ошибки или FALSE в случае ошибки.
**get_charset()** - определяет кодировку ответа сервера и возвращает её. Порядок определения кодировки:
1. HTTP заголовки
2. Тег &lt;meta&gt;
3. mb_detect_encoding()

**set_headers($key, $value = '')** - добавляет отправляемые заголовки. Пример вызова:
```php
$request->set_headers('Accept-Encoding', 'gzip, deflate, br');
```
**dump()** - возвращает полный отчет о запросе оформленный с помощью HTML.

### Переменные класса request
**request::response **- ответ сервера без заголовком
**request::info** - массив заголовков ответа сервера
**request::headers** - строка заголовков ответа сервера
**request::error_code** - код ошибки, если имеется
**request::error_msg** - сообщение об ошибке, если имеется

## Функции класса request_session
**\_\_construct($flags = 0)** - принимает параметром флаги. Доступные флаги:
> REQUEST_SESS_TEMP - использовать временной место хранения сессии, иначе требуется установить папку сессии функцией set_sess_dir().
REQUEST_SESS_NEW - сессия новая, не требуется очистить папку с сессией.

**curl_opt($name, $value)** - устанавливает параметр CURL для всей сессии.
**request_opt($name, $value)** - устанавливает параметр для класса для всей сессии.
**get_user_agent()** - получить используемое имя браузера.
**set_user_agent($user_agent)** - устанавливает имя браузера. По умолчанию: *Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36*.
**set_headers($key, $value = '')** - устанавливает передаваемые заголовки для всей сессии.
**export()** - возвращает все данные сессии в тектовом виде для удобного хранения и переноса.
**import($settings)** - принимает данные сессии полученные из export() и позволяет продолжить сессию.
**set_sess_dir($directory)** - устанавливает папку для хранения данных сессии. В данной версии в папке сохраняются только файлы cookie, для полного сохранения данных используйте export().
**get_sess_dir()** - возвращает папку хранения cookie, FALSE если она не установлена.
**get_cookie_file()** - возвращает путь к файлу с cookie. Если установлен флаг REQUEST_SESS_TEMP, возвращается путь к временному файлу.
**get_cookie_string() **- возвращает все cookie в формате Netscape HTTP Cookie.
**set_cookie_string($cookie)** - устанавливает cookie. Параметр $cookie в формате Netscape HTTP Cookie.
**set_cookie($cookie)** - добавляет cookie. Параметр $cookie передается в виде массива со значениями domain, domainonly, path, secure, expires, key, value. Предустановленные параметры:
```php
(
			'domain'	  => '',
			'domain_only' => 'FALSE',
			'path'		  => '/',
			'secure'	  => 'FALSE',
			'expires'	  => 0,
			'key'		  => '',
			'value'	      => '',
		)
```
**get_cookie()** - возвращает массив cookie в формате, как в функии set_cookie()

## Примеры
```php
<?php

/*
*
*	Создаем сессию и предустанавливает прокси
*
*/

$r_sess = new request_session(REQUEST_SESS_TEMP);
$r_sess->curl_opt(CURLOPT_PROXY, 'socks5://user:passw@23.45.67.89:80');


/*
*
*	Отправляем запрос на страницу авторизации.
*	Часто оттуда нам надо полуить CSRF код, обычно он называется token
*
*/

$request = new request('http://site.ru/login');
$request->session($r_sess);
$request->send();

if( $request->error())
{
	//
	//	Если произошла ошибка обрываем код и выводим отчет о запросе
	//
	
	die($request->dump());
}

/*
*
*	Ищем код в ответе сервера
*
*/

preg_match('#name="token" value="(.+?)"#', $request->response, $matches);

/*
*
*	Формируем POST массив
*
*/

$data = array(
	'email' => 'vovka@mail.ru',
	'passwd' => 'qwerty123',
	'token' => $matches[1]
);

/*
*
*	Отправляем данные
*	Установим нашу сессию, чтобы были использованы прокси и cookie с прошлого запроса
*
*/

$request = new request('http://site.ru/login');
$request->session($r_sess);
$request->set(CURLOPT_FOLLOWLOCATION, false); // Отключим следование заголовкам Location, т.к. оно включено по умолчанию
$request->send();

/*
*
*	Смотрим код ответа сервера
*	Если произошел редирект, код 301
*	Значит мы удачно авторизованы
*
*/

if( $request->info['http_code'] === 301)
{
	echo 'Ok';
}
else
{
	echo 'Fail';
}
```
