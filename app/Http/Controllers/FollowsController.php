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
use App\Follower;
use App\Block;
use Response;
use Carbon\Carbon;
use File;
use \Image;
use Vinkla\Pusher\Facades\Pusher;

class FollowsController extends Controller
{
  public function __construct()
  {
    $this->middleware('jwt.auth');
  }

  public function getFollowTopics()
  {
    $user = Auth::user();
    $follows = Follower::where('followers.followerID', '=', $user->id)->where('followers.approve', '=', 1)->join('profiles', 'followers.userID', '=', 'profiles.userID')->orderBy('profiles.profileTopics')->take(30)->get();

    $topics = array();
    foreach($follows as $fkey => $follow)
    {
      $topic = Topic::where('topics.userID', '=', $follow->userID)->join('users', 'topics.userID', '=', 'users.id')->where('users.ban', '=', 0)->where('users.inactive', '=', 0)->join('profiles', 'users.id', '=', 'profiles.userID')->orderBy('topics.created_at', 'DESC')->select('topics.id', 'topics.userID', 'topics.topicBody', 'topics.topicThumbnail', 'topics.topicTags', 'topics.topicReplies', 'topics.topicReplies', 'topics.topicVotes', 'topics.created_at', 'users.name', 'users.avatar', 'profiles.profileName')->paginate(1);
      if(!$topic->isEmpty())
      {
        $topic[0]->topicDate = Carbon::createFromTimeStamp(strtotime($topic[0]->created_at))->diffForHumans();
        $vote = Vote::where('userID', '=', $user->id)->where('topicID', '=', $topic[0]->id)->first();
        if(!empty($votes))
        {
          if($vote->vote == 1)
          {
            $topic[0]->vote = 1;
          }
          else if($vote->vote == 0)
          {
            $topic[0]->vote = 2;
          }
        }
        else {
          $topic[0]->vote = 0;
        }

        $topics[] = $topic[0];
      }
    }

    return Response::json($topics);
  }

  public function suggestFollows()
  {
    $user = Auth::user();
    $profiles = User::where('users.ban', '=', 0)->where('users.inactive', '=', 0)->where('users.id', '!=', $user->id)->join('profiles', 'users.id', '=', 'profiles.userID')->orderBy('profiles.profileTopics', 'DESC')->select('users.id', 'users.name', 'users.avatar', 'profiles.profileName')->take(100)->get();

    $profileArray = array();
    foreach($profiles as $pkey => $profile)
    {
      $follow = Follower::where('followerID', '=', $user->id)->where('userID', '=', $profile->id)->select('id', 'userID')->first();
      if(empty($follow))
      {
        if(count($profileArray) < 15)
        {
          $profileArray[] = $profile;
        }
      }
    }

    return Response::json($profileArray);
  }

  public function getFollowing($id)
  {
    $user = Auth::user();
    $settings = Setting::where('userID', '=', $id)->select('profPrivate')->first();
    if($settings->profPrivate == 0)
    {
      $followers = Follower::where('followers.followerID', '=', $id)->where('approve', '=', 1)->join('users', 'followers.userID', '=', 'users.id')->where('users.ban', '=', 0)->where('users.inactive', '=', 0)->join('profiles', 'users.id', '=', 'profiles.userID')->select('followers.id', 'followers.userID', 'followers.followerID', 'followers.approve', 'followers.deny', 'users.name', 'profiles.profileName', 'users.avatar')->orderBy('followers.created_at', 'DESC')->paginate(50);
      foreach($followers as $fkey => $follower)
      {
        $followers[$fkey]['follow'] = 0;

        $checkFollow = Follower::where('userID', '=', $follower->userID)->where('followerID', '=', $user->id)->where('approve', '=', 1)->first();
        if(!empty($checkFollow))
        {
          $followers[$fkey]['follow'] = 1;
        }

        $checkFollow = Follower::where('userID', '=', $follower->userID)->where('followerID', '=', $user->id)->where('approve', '=', 0)->where('deny', '=', 0)->first();
        if(!empty($checkFollow))
        {
          $followers[$fkey]['follow'] = 2;
        }

        $checkFollow = Follower::where('userID', '=', $follower->userID)->where('followerID', '=', $user->ID)->where('deny', '=', 1)->first();
        if(!empty($checkFollow))
        {
          $followers[$fkey]['follow'] = 3;
        }

        if($follower->userID == $user->id)
        {
          $followers[$fkey]['follow'] = 4;
        }
      }
    }
    else {
      $followers = [];
    }

    return Response::json($followers);
  }

  public function getFollowers($id)
  {
    $user = Auth::user();
    $settings = Setting::where('userID', '=', $id)->select('profPrivate')->first();
    if($settings->profPrivate == 0)
    {
      $followers = Follower::where('followers.userID', '=', $id)->where('approve', '=', 1)->join('users', 'followers.followerID', '=', 'users.id')->where('users.ban', '=', 0)->where('users.inactive', '=', 0)->join('profiles', 'users.id', '=', 'profiles.userID')->select('followers.id', 'followers.userID', 'followers.followerID', 'followers.approve', 'followers.deny', 'users.name', 'profiles.profileName', 'users.avatar')->orderBy('followers.created_at', 'DESC')->paginate(50);
      foreach($followers as $fkey => $follower)
      {
        $followers[$fkey]['follow'] = 0;

        $checkFollow = Follower::where('userID', '=', $follower->followerID)->where('followerID', '=', $user->id)->where('approve', '=', 1)->first();
        if(!empty($checkFollow))
        {
          $followers[$fkey]['follow'] = 1;
        }

        $checkFollow = Follower::where('userID', '=', $follower->followerID)->where('followerID', '=', $user->id)->where('approve', '=', 0)->where('deny', '=', 0)->first();
        if(!empty($checkFollow))
        {
          $followers[$fkey]['follow'] = 2;
        }

        $checkFollow = Follower::where('userID', '=', $follower->followerID)->where('followerID', '=', $user->ID)->where('deny', '=', 1)->first();
        if(!empty($checkFollow))
        {
          $followers[$fkey]['follow'] = 3;
        }

        if($follower->followerID == $user->id)
        {
          $followers[$fkey]['follow'] = 4;
        }
      }
    }
    else {
      $followers = [];
    }

    return Response::json($followers);
  }

  public function storeFollower(Request $request)
  {
    $rules = array(
      'userID' => 'required'
    );
    $validator = Validator::make($request->json()->all(), $rules);

    if ($validator->fails()) {
        return Response::json(0);
    } else {

      $followerID = Auth::user()->id;
      $userID = $request->json('userID');
      $profile = Profile::where('userID', '=', $userID)->first();
      $settings = Setting::where('userID', '=', $userID)->select('profPrivate')->first();

      if($userID != $followerID)
      {
        $check = Follower::where('userID', '=', $userID)->where('followerID', '=', $followerID)->first();
        if(empty($check))
        {
          if($settings->profPrivate == 0)
          {
            $follow = new Follower;
            $follow->userID = $userID;
            $follow->followerID = $followerID;
            $follow->approve = 1;
            $follow->deny = 0;
            $follow->save();

            $notif = new Notification;
            $notif->userID = $userID;
            $notif->topicID = 0;
            $notif->peerID = $followerID;
            $notif->notiType = "Follower";
            $notif->read = 0;
            $notif->save();

            return Response::json(1);
          }
          else if($settings->profPrivate == 1)
          {
            $follow = new Follower;
            $follow->userID = $userID;
            $follow->followerID = $followerID;
            $follow->approve = 0;
            $follow->deny = 0;
            $follow->save();

            $notif = new Notification;
            $notif->userID = $userID;
            $notif->topicID = 0;
            $notif->peerID = $followerID;
            $notif->notiType = "Request";
            $notif->read = 0;
            $notif->save();


            return Response::json(4);
          }
        }
        else if($check->approve == 1 && $check->deny == 0)
        {
          $check->delete();

          return Response::json(2);
        }
        else if($check->approve == 0 && $check->deny == 1)
        {
          return Response::json(5);
        }
      } else {
        return Response::json(3);
      }
    }
  }

  public function acceptRequest($id, $peer)
  {
    $user = Auth::user();
    $follower = Follower::where('followerID', '=', $peer)->where('userID', '=', $user->id)->first();
    $follower->approve = 1;
    $follower->deny = 0;
    $follower->save();

    $peerNoti = Notification::find($id);
    if(!empty($peerNoti))
    {
      $peerNoti->delete();
    }

    $notif = new Notification;
    $notif->userID = $follower->followerID;
    $notif->topicID = 0;
    $notif->peerID = $user->id;
    $notif->notiType = "Accept";
    $notif->read = 0;
    $notif->save();

    return Response::json(1);
  }

  public function denyRequest($id, $peer)
  {
    $user = Auth::user();
    $follower = Follower::where('followerID', '=', $peer)->where('userID', '=', $user->id)->where('approve', '=', 0)->first();
    $follower->approve = 0;
    $follower->deny = 1;
    $follower->save();

    $peerNoti = Notification::find($id);
    if(!empty($peerNoti))
    {
      $peerNoti->delete();
    }

    return Response::json(1);
  }


}
