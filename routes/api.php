<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post('signUp', 'AuthenticateController@signUp');
Route::post('signIn', 'AuthenticateController@signIn');
Route::post('socialSignOn', 'AuthenticateController@socialSignOn');
Route::get('authenticate/user', 'AuthenticateController@getAuthenticatedUser');
Route::post('resetPassword', 'AuthenticateController@resetPassword');

/*Route::get('auth/facebook', 'AuthenticateController@redirectToFacebook');
Route::get('auth/facebook/callback', 'AuthenticateController@handleFacebookCallback');
Route::get('auth/twitter', 'AuthenticateController@redirectToTwitter');
Route::get('auth/twitter/callback', 'AuthenticateController@handleTwitterCallback');
Route::get('auth/google', 'AuthenticateController@redirectToGoogle');
Route::get('auth/google/callback', 'AuthenticateController@handleGoogleCallback');*/

Route::get('getTopics', 'TopicsController@getTopics');
Route::get('getHot', 'TopicsController@getHot');
//Route::get('getFeature', 'BragsController@getFeature');
Route::get('getFollowTopics', 'FollowsController@getFollowTopics');
Route::get('suggestFollows', 'FollowsController@suggestFollows');

Route::post('storeTopic', 'TopicsController@storeTopic');
Route::post('updateTopic/{id}', 'TopicsController@updateTopic');
Route::post('voteTopic/{id}', 'TopicsController@voteTopic');
Route::get('showTopic/{id}', 'TopicsController@showTopic');
Route::post('deleteTopic/{id}', 'TopicsController@deleteTopic');
Route::get('getReplies/{id}', 'RepliesController@getReplies');
Route::get('getUsers', 'UsersController@getUsers');
Route::get('getTaggable', 'SearchController@getTaggable');
Route::get('getTags', 'SearchController@getTags');
Route::post('searchTag', 'SearchController@searchTag');
Route::post('searchTopics', 'SearchController@searchTopics');
Route::post('reportTopic/{id}', 'TopicsController@reportTopic');
Route::post('unReportTopic/{id}', 'TopicsController@unReportTopic');

Route::get('getProfile/{id}', 'UsersController@getProfile');
Route::post('updateProfile/{id}', 'UsersController@updateProfile');
Route::post('reportProfile/{id}', 'UsersController@reportProfile');
Route::post('unReportProfile/{id}', 'UsersController@unReportProfile');
Route::post('banUser/{id}', 'UsersController@banUser');
Route::get('getFollowers/{id}', 'FollowsController@getFollowers');
Route::get('getFollowing/{id}', 'FollowsController@getFollowing');
Route::post('storeFollower', 'FollowsController@storeFollower');
Route::get('getSettings', 'UsersController@getSettings');
Route::post('updateSettings', 'UsersController@updateSettings');
Route::post('deactivateProfile', 'UsersController@deactivateProfile');
Route::get('profileTopics/{id}', 'UsersController@profileTopics');

Route::get('getNotifs', 'UsersController@getNotifs');
Route::get('getNotifCount', 'UsersController@getNotifCount');
Route::post('deleteNotif/{id}', 'UsersController@deleteNotif');
Route::post('acceptRequest/{id}/{peer}', 'FollowsController@acceptRequest');
Route::post('denyRequest/{id}/{peer}', 'FollowsController@denyRequest');
Route::get('readNotifs', 'UsersController@readNotifs');

Route::post('storeReply', 'RepliesController@storeReply');
Route::post('deleteReply/{id}', 'RepliesController@deleteReply');
Route::post('reportReply/{id}', 'RepliesController@reportReply');
Route::post('unReportReply/{id}', 'RepliesController@unReportReply');



Route::any('{path?}', 'MainController@index')->where("path", ".+");
