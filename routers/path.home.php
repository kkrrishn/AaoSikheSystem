<?php



$router->get('/logout', 'AuthController@logout', 'logout');

// Public
$router->get('/', 'HomeController@index', 'home');
$router->get('/register','AuthController@register','register');
$router->get('/login', 'AuthController@login', 'login');
$router->get('/about', 'HomeController@about', 'about');
$router->get('/gallery', 'HomeController@gallery', 'gallery');

$router->get('/l', 'HomeController@l', 'l');
$router->get('/r', 'HomeController@r', 'r');

$router->get('/contact', 'HomeController@contact', 'contact');
$router->post('/contact', 'HomeController@handleContact', 'contact.submit');

$router->get('/course', 'HomeController@course', 'course');
$router->get('/course/{id}/{slug}', 'HomeController@course', 'course.view');
$router->get('/offer', 'HomeController@offer', 'offer');
$router->get('/offer/{id}/{slug}', 'HomeController@offer', 'offer.view');
$router->get('/event', 'HomeController@event', 'event');
$router->get('/event/{id}/{slug}', 'HomeController@event', 'event.view');
$router->get('/terms-of-service', 'HomeController@index', 'terms.of.service');
$router->get('/privacy-policy', 'HomeController@index', 'privacy.policy');

$router->post('/login/submit', 'AuthController@handleLogin', 'login.submit');

$router->post('/home/ajax/courses', 'CourseController@getCourse_home', 'home.ajax.courses');