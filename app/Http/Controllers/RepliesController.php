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

class RepliesController extends Controller
{
  public function __construct()
  {
    $this->middleware('jwt.auth');
  }

  public function getReplies($id)
  {
    $replies = Reply::where('replies.topicID', '=', $id)->join('users', 'replies.userID', '=', 'users.id')->where('users.ban', '=', 0)->where('users.inactive', '=', 0)->join('profiles', 'users.id', '=', 'profiles.userID')->select('replies.id', 'replies.userID', 'replies.replyBody', 'replies.replyFlag', 'replies.created_at', 'users.name', 'users.avatar', 'profiles.profileName')->orderBy('created_at', 'DESC')->paginate(30);
    foreach($replies as $rkey => $reply)
    {
      $reply->replyDate = Carbon::createFromTimeStamp(strtotime($reply->created_at))->diffForHumans();
    }

    return Response::json($replies);
  }

  public function getRealReplies($id)
  {
    $replies = Reply::where('replies.topicID', '=', $id)->join('users', 'replies.userID', '=', 'users.id')->where('users.ban', '=', 0)->where('users.inactive', '=', 0)->join('profiles', 'users.id', '=', 'profiles.userID')->select('replies.id', 'replies.topicID', 'replies.userID', 'replies.replyBody', 'replies.replyFlag', 'replies.created_at', 'users.name', 'users.avatar', 'profiles.profileName')->orderBy('created_at', 'DESC')->paginate(30);

    $rArray = array();

    $rArray['total'] = $replies->total();
    $rArray['per_page'] = $replies->perPage();
    $rArray['current_page'] = $replies->currentPage();
    $rArray['last_page'] = $replies->lastPage();
    $rArray['next_page_url'] = $replies->nextPageUrl();
    $rArray['prev_page_url'] = $replies->previousPageUrl();
    $rArray['from'] = $replies->firstItem();
    $rArray['to'] = $replies->lastItem();


    foreach($replies as $rkey => $reply)
    {
      $rArray['data'][$rkey]['_id'] = $reply->id;
      $rArray['data'][$rkey]['text'] = $reply->replyBody;
      $rArray['data'][$rkey]['createdAt'] = $reply->created_at->timestamp;
      $rArray['data'][$rkey]['user']['_id'] = $reply->userID;
      $rArray['data'][$rkey]['user']['name'] = $reply->profileName;
      $rArray['data'][$rkey]['user']['avatar'] = $reply->avatar;
    }

    return Response::json($rArray);
  }

  public function storeReply(Request $request)
  {
    $rules = array(
      'topicID'		=> 	'required',
      'replyBody' => 'required'
    );
    $validator = Validator::make($request->json()->all(), $rules);

    if ($validator->fails()) {
        return Response::json(0);
    } else {

      $user = Auth::user();
      $topicID = $request->json('topicID');
      $replyBody = $request->json('replyBody');
      $replyMentions = $request->json('replyMentions');

      $user = User::find($user->id);
      if($user->ban == 1)
      {
        return Response::json(2);
      }

      $current = Carbon::now();
      $checkReply = Reply::where('userID', '=', $user->id)->where('topicID', '=', $topicID)->orderBy('created_at', 'DESC')->select('created_at')->first();
      if(!empty($checkReply))
      {
        $replyTime  = Carbon::createFromTimeStamp(strtotime($checkReply->created_at));
        if($current->diffInMinutes($replyTime) < 2)
        {
          return Response::json(5);
        }
      }

      if(strlen($replyBody) > 1000)
      {
        return Response::json(4);
      }

      $topic = Topic::find($topicID);

      if($topic->topicReplies >= 100)
      {
        return Response::json(6);
      }

      if(!empty($replyMentions))
      {
        if(count($replyMentions) > 1)
        {
          return Response::json(3);
        }
        else {
          foreach($replyMentions as $mkey => $mention)
          {
            $mReply = Reply::find($mention['id']);
            $mUser = User::find($mReply->userID);
            $mSettings = Setting::where('userID', '=', $mUser->id)->first();
            if($mSettings->notiMention == 1)
            {
              $notif = new Notification;
              $notif->userID = $mUser->id;
              $notif->topicID = $topic->id;
              $notif->peerID = $user->id;
              $notif->notiType = "Mention";
              $notif->save();
            }
          }
        }
      }

      $reply = new Reply;
      $reply->userID = $user->id;
      $reply->topicID = $topic->id;
      $reply->replyBody = $replyBody;
      $reply->save();

      $topic->topicReplies = $topic->topicReplies + 1;
      $topic->save();

      $profile = Profile::where('userID', '=', $user->id)->first();
      $profile->profileReplies = $profile->profileReplies + 1;
      $profile->profileScore = $profile->profileScore + 5;
      $profile->save();

      $settings = Setting::where('userID', '=', $topic->userID)->first();
      if($settings->notiReply == 1)
      {
        if($topic->userID != $user->id)
        {
          $notif = new Notification;
          $notif->userID = $topic->userID;
          $notif->topicID = $topic->id;
          $notif->peerID = $user->id;
          $notif->notiType = "Reply";
          $notif->save();
        }
      }

      $previous = Reply::where('topicID', '=', $topic->id)->take(10)->get();
      foreach($previous as $key => $value)
      {
        $pSettings = Setting::where('userID', '=', $value->userID)->first();
        if($pSettings->notiBounce == 1)
        {
          if($value->userID != $user->id)
          {
            $notif = new Notification;
            $notif->userID = $value->userID;
            $notif->topicID = $topic->id;
            $notif->peerID = $user->id;
            $notif->notiType = "Bounce";
            $notif->save();
          }
        }
      }

      $replyData = Reply::where('replies.id', '=', $reply->id)->join('users', 'replies.userID', '=', 'users.id')->where('users.ban', '=', 0)->where('users.inactive', '=', 0)->join('profiles', 'users.id', '=', 'profiles.userID')->select('replies.id', 'replies.topicID', 'replies.userID', 'replies.replyBody', 'replies.created_at', 'users.name', 'users.avatar', 'profiles.profileName')->first();
      $replyData->replyDate = Carbon::createFromTimeStamp(strtotime($replyData->created_at))->diffForHumans();

      return Response::json($replyData);
    }
  }

  public function storeRealReply(Request $request)
  {
    $rules = array(
      'topicID'		=> 	'required',
      'replyBody' => 'required'
    );
    $validator = Validator::make($request->json()->all(), $rules);

    if ($validator->fails()) {
        return Response::json(0);
    } else {

      $user = Auth::user();
      $topicID = $request->json('topicID');
      $replyBody = $request->json('replyBody');
      $replyMentions = $request->json('replyMentions');

      $user = User::find($user->id);
      if($user->ban == 1)
      {
        return Response::json(2);
      }

      $current = Carbon::now();
      $checkReply = Reply::where('userID', '=', $user->id)->where('topicID', '=', $topicID)->orderBy('created_at', 'DESC')->select('created_at')->first();
      if(!empty($checkReply))
      {
        $replyTime  = Carbon::createFromTimeStamp(strtotime($checkReply->created_at));
        if($current->diffInMinutes($replyTime) < 2)
        {
          return Response::json(5);
        }
      }

      if(strlen($replyBody) > 1000)
      {
        return Response::json(4);
      }

      $topic = Topic::find($topicID);

      if($topic->topicReplies >= 100)
      {
        return Response::json(6);
      }

      if(!empty($replyMentions))
      {
        if(count($replyMentions) > 1)
        {
          return Response::json(3);
        }
        else {
          foreach($replyMentions as $mkey => $mention)
          {
            $mReply = Reply::find($mention['id']);
            $mUser = User::find($mReply->userID);
            $mSettings = Setting::where('userID', '=', $mUser->id)->first();
            if($mSettings->notiMention == 1)
            {
              $notif = new Notification;
              $notif->userID = $mUser->id;
              $notif->topicID = $topic->id;
              $notif->peerID = $user->id;
              $notif->notiType = "Mention";
              $notif->save();
            }
          }
        }
      }

      $reply = new Reply;
      $reply->userID = $user->id;
      $reply->topicID = $topic->id;
      $reply->replyBody = $replyBody;
      $reply->save();

      $topic->topicReplies = $topic->topicReplies + 1;
      $topic->save();

      $profile = Profile::where('userID', '=', $user->id)->first();
      $profile->profileReplies = $profile->profileReplies + 1;
      $profile->profileScore = $profile->profileScore + 5;
      $profile->save();

      $settings = Setting::where('userID', '=', $topic->userID)->first();
      if($settings->notiReply == 1)
      {
        if($topic->userID != $user->id)
        {
          $notif = new Notification;
          $notif->userID = $topic->userID;
          $notif->topicID = $topic->id;
          $notif->peerID = $user->id;
          $notif->notiType = "Reply";
          $notif->save();
        }
      }

      $previous = Reply::where('topicID', '=', $topic->id)->take(10)->get();
      foreach($previous as $key => $value)
      {
        $pSettings = Setting::where('userID', '=', $value->userID)->first();
        if($pSettings->notiBounce == 1)
        {
          if($value->userID != $user->id)
          {
            $notif = new Notification;
            $notif->userID = $value->userID;
            $notif->topicID = $topic->id;
            $notif->peerID = $user->id;
            $notif->notiType = "Bounce";
            $notif->save();
          }
        }
      }

      $replyData = Reply::where('replies.id', '=', $reply->id)->join('users', 'replies.userID', '=', 'users.id')->where('users.ban', '=', 0)->where('users.inactive', '=', 0)->join('profiles', 'users.id', '=', 'profiles.userID')->select('replies.id', 'replies.topicID', 'replies.userID', 'replies.replyBody', 'replies.created_at', 'users.name', 'users.avatar', 'profiles.profileName')->first();
      $rArray = array();
      $rArray[0]['_id'] = $replyData->id;
      $rArray[0]['text'] = $replyData->replyBody;
      $rArray[0]['createdAt'] = $replyData->created_at->timestamp;
      $rArray[0]['user']['_id'] = $replyData->userID;
      $rArray[0]['user']['name'] = $replyData->profileName;
      $rArray[0]['user']['avatar'] = $replyData->avatar;

      $replyChan = "topic-".$replyData->topicID;
      Pusher::trigger($replyChan, 'replySend', $rArray[0]);

      return Response::json(1);
    }
  }
  
  public function reportReply($id)
  {
    $user = Auth::user();
    if(empty($user))
    {
      return Response::json(0);
    }
    else {
      $reply = Reply::find($id);
      if($reply->userID == $user->id)
      {
        return Response::json(2);
      }
      $reply->replyFlag = 1;
      $reply->save();

      $admins = User::where('role', '=', 1)->where('ban', '=', 0)->where('inactive', '=', 0)->get();
      foreach($admins as $akey => $admin)
      {
        $notif = new Notification;
        $notif->userID = $admin->id;
        $notif->topicID = $reply->id;
        $notif->peerID = $user->id;
        $notif->notiType = "Reply Report";
        $notif->save();
      }

      return Response::json(1);
    }
  }

  public function unReportReply($id)
  {
    $user = Auth::user();
    if(empty($user))
    {
      return Response::json(0);
    }
    else {
      if($user->role == 1)
      {
        $reply = Topic::find($id);
        $reply->replyFlag = 0;
        $reply->save();

        return Response::json(1);
      }
      else {
        return Response::json(0);
      }
    }
  }

  public function updateReply(Request $request, $id)
  {
    $rules = array(
      'topicID'		=> 	'required',
      'replyBody' => 'required'
    );
    $validator = Validator::make($request->json()->all(), $rules);

    if ($validator->fails()) {
        return Response::json(0);
    } else {

      $user = Auth::user();
      $topicID = $request->json('topicID');
      $replyBody = $request->json('replyBody');
      $replyMentions = $request->json('replyMentions');

      $user = User::find($user->id);
      if($user->role != 1)
      {
        return Response::json(2);
      }

      $topic = Topic::find($topicID);

      $reply = Reply::find($id);
      $reply->replyBody = $replyBody;
      $reply->save();

      $replyData = Reply::where('replies.id', '=', $reply->id)->join('users', 'replies.userID', '=', 'users.id')->select('replies.id', 'replies.replyBody', 'users.name', 'users.avatar')->get();
      return Response::json($replyData);
    }
  }

  public function deleteReply($id)
  {
    $user = Auth::user();
    if($user->role == 1)
    {
      $reply = Reply::find($id);

      $author = Profile::where('userID', '=', $reply->userID)->first();
      $author->profileReplies = $author->profileReplies - 1;
      $author->profileScore = $author->profileScore - 5;
      $author->save();

      $topic = Topic::find($reply->topicID);
      $topic->topicReplies = $topic->topicReplies - 1;
      $topic->save();

      $reply->delete();

      return Response::json(1);
    }
  }


}
