<?php

/*
|--------------------------------------------------------------------------
| AI assistant routes
|--------------------------------------------------------------------------
|
| Loaded by RouteServiceProvider::mapAssistantRoutes() in the "assistant" middleware group:
| the same stack as "web", but with a session of its own (cookie and folder) and a CSRF check
| that does not write the shared XSRF-TOKEN cookie. See AssistantSessionConfig.
|
| Same URLs and names as before; they were in routes/web.php. They are registered BEFORE
| web.php so the catch-all GET /{slug} there can never capture /ai-assistant.
|
*/

Route::get('/ai-assistant', 'AiAssistant\ChatController@index')->name('ai-assistant.index');
Route::post('/ai-assistant/message', 'AiAssistant\ChatController@converse')->name('ai-assistant.message');
Route::post('/ai-assistant/reset', 'AiAssistant\ChatController@reset')->name('ai-assistant.reset');
Route::post('/ai-assistant/edit', 'AiAssistant\ChatController@edit')->name('ai-assistant.edit');
