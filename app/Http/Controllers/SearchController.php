<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Auth;
use App\Topic;
use App\User;
use App\Profile;
use App\Vote;
use App\Notification;
use App\Setting;
use App\Reply;
use App\Tag;
use App\Experience;
use App\Follower;
use App\Message;
use App\Room;
use App\Block;
use Response;
use Carbon\Carbon;
use File;
use \Image;
use Vinkla\Pusher\Facades\Pusher;

class SearchController extends Controller
{
  public function __construct()
  {
    $this->middleware('jwt.auth');
  }

  public function getTaggable()
  {
    $tags = Tag::orderBy('updated_at', 'DESC')->select('id','tagName')->take(100)->get();

    return Response::json($tags);
  }

  public function getTags()
  {
    $tags = Tag::orderBy('tagCount', 'DESC')->select('id','tagName', 'tagCount')->paginate(40);

    return Response::json($tags);
  }

  public function searchTag(Request $request)
  {
    $rules = array(
      'id' => 'required'
    );
    $validator = Validator::make($request->json()->all(), $rules);

    if ($validator->fails()) {
        return Response::json(0);
    } else {

      $id = $request->json('id');
      $tag = Tag::find($id);

      $topics = Topic::where('topics.topicTags', 'LIKE', '%'.$tag->tagName.'%')->join('users', 'topics.userID', '=', 'users.id')->where('users.ban', '=', 0)->where('users.inactive', '=', 0)->join('profiles', 'users.id', '=', 'profiles.userID')->orderBy('topics.created_at', 'DESC')->select('topics.id', 'topics.userID', 'topics.topicBody', 'topics.topicThumbnail', 'topics.topicTags', 'topics.topicReplies', 'topics.topicReplies', 'topics.topicVotes', 'topics.created_at', 'users.name', 'users.avatar', 'profiles.profileName')->paginate(30);
      foreach($topics as $tkey => $topic)
      {
        $topic->topicDate = Carbon::createFromTimeStamp(strtotime($topic->created_at))->diffForHumans();
      }

      return Response::json($topics);
    }
  }

  public function searchTopics(Request $request)
  {
    $rules = array(
      'searchContent' => 'required'
    );
    $validator = Validator::make($request->json()->all(), $rules);

    if ($validator->fails()) {
        return Response::json(0);
    } else {

      $searchContent = $request->json('searchContent');

      $topics = Topic::where('topics.topicTags', 'LIKE', '%'.$searchContent.'%')->orWhere('topics.topicBody', 'LIKE', '%'.$searchContent.'%')->join('users', 'topics.userID', '=', 'users.id')->where('users.ban', '=', 0)->where('users.inactive', '=', 0)->join('profiles', 'users.id', '=', 'profiles.userID')->orderBy('topics.created_at', 'DESC')->select('topics.id', 'topics.userID', 'topics.topicBody', 'topics.topicThumbnail', 'topics.topicTags', 'topics.topicReplies', 'topics.topicReplies', 'topics.topicVotes', 'topics.created_at', 'users.name', 'users.avatar', 'profiles.profileName')->paginate(30);
      foreach($topics as $tkey => $topic)
      {
        $topic->topicDate = Carbon::createFromTimeStamp(strtotime($topic->created_at))->diffForHumans();
      }

      return Response::json($topics);
    }
  }

}
