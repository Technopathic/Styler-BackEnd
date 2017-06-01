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

class UsersController extends Controller
{

  public function __construct()
  {
    $this->middleware('jwt.auth');
  }

  public function reportProfile($id)
  {
    $user = Auth::user();
    if(empty($user))
    {
      return Response::json(0);
    }
    else if($user->id == $id)
    {
      return Response::json(2);
    }
    else {
      $profile = Profile::where('userID', '=', $id)->first();
      $profile->profileFlag = 1;
      $profile->save();

      $admins = User::where('role', '=', 1)->where('ban', '=', 0)->where('inactive', '=', 0)->get();
      foreach($admins as $akey => $admin)
      {
        $notif = new Notification;
        $notif->userID = $admin->id;
        $notif->topicID = $profile->id;
        $notif->peerID = $user->id;
        $notif->notiType = "Profile Report";
        $notif->save();
      }

      return Response::json(1);
    }
  }

  public function unReportProfile($id)
  {
    $user = Auth::user();
    if(empty($user))
    {
      return Response::json(0);
    }
    else {
      if($user->role == 1)
      {
        $profile = Profile::where('userID', '=', $id)->first();
        $profile->profileFlag = 0;
        $profile->save();

        return Response::json(1);
      }
      else {
        return Response::json(0);
      }
    }
  }

  public function getProfile($id)
  {
    $user = User::where('id', '=', $id)->select('id', 'name', 'email', 'avatar', 'ban', 'inactive')->first();
    if($user->ban == 1 || $user->inactive == 1)
    {
      return Response::json(0);
    }

    $settings = Setting::where('userID', '=', $user->id)->select('profPrivate')->first();
    $profile = Profile::where('userID', '=', $user->id)->select('profileName', 'profileLocation', 'profileDesc', 'profileWebsite', 'profileTopics', 'profileVotes', 'profileReplies', 'profileFlag')->first();
    $followers = Follower::where('userID', '=', $user->id)->where('approve', '=', 1)->count();
    $profile->profileFollowers = $followers;
    $following = Follower::where('followerID', '=', $user->id)->where('approve', '=', 1)->count();
    $profile->profileFollowing = $following;

    if($settings->profPrivate == 1)
    {
      if($user->id != Auth::user()->id)
      {
        $follower = Follower::where('userID', '=', $user->id)->where('followerID', '=', Auth::user()->id)->first();
        if(empty($follower))
        {
          $profile->profileLocation = null;
          $profile->profileDesc = "This Profile is Private.";
          $profile->profileWebsite = null;
        }
      }
    }

    $follow = 0;
    if($user->id != Auth::user()->id)
    {
      $check = Follower::where('userID', '=', $user->id)->where('followerID', '=', Auth::user()->id)->first();
      if(empty($check))
      {
        $follow = 0;
      }
      else {
        if($check->approve == 1)
        {
          $follow = 1;
        }
        else if($check->approve == 0 && $check->deny == 0)
        {
          $follow = 2;
        }
        else if($check->approve == 0 && $check->deny == 1)
        {
          $follow = 3;
        }
      }
    }
    else {
      $follow = 4;
    }

    return Response::json(['user' => $user, 'profile' => $profile, 'follow' => $follow]);
  }

  public function updateProfile(Request $request, $id)
  {
    $rules = array(

    );
    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        return Response::json(['error' => 'Please fill out all fields.']);
    } else {

      $user = Auth::user();
      $userObject = User::find($user->id);
      $profile = Profile::where('userID', '=', $id)->first();

      if($user->id == $profile->userID || $user->role == 1)
      {
        $profileName = $request->input('profileName');
        //$profilePhone = $request->json('profilePhone');
        //$profileSocial = $request->json('profileSocial');
        $profileLocation = $request->input('profileLocation');
        $profileDesc = $request->input('profileDesc');
        //$profileLanguage = $request->json('profileLanguage');
        $profileWebsite = $request->input('profileWebsite');

        $newPassword = $request->input('newPassword');
        $confirmPassword = $request->input('confirmPassword');

        if(strlen($profileName) > 32)
        {
          return Response::json(['error' => 'Your name is too long.']);
        }

        if(strlen($profileLocation) > 64)
        {
          return Response::json(['error' => 'Your location is too long.']);
        }

        if(strlen($profileDesc) > 5000)
        {
          return Response::json(['error' => 'Your description is too long.']);
        }

        if(strlen($profileWebsite) > 64)
        {
          return Response::json(['Your web URL is too long.']);
        }

        if(strlen($newPassword) > 128)
        {
          return Response::json(['error' => 'Your password is too long.']);
        }

        if($newPassword != $confirmPassword)
        {
          return Response::json(['error' => 'Your passwords do not match.']);
        }

        if($request->input('avatar'))
        {
          $imageFile = 'storage/media/users/avatars';
          if (!is_dir($imageFile)) {
            mkdir($imageFile,0777,true);
          }

          $string = str_random(15);
          $avatarImg = Image::make($request->input('avatar'));

          if($avatarImg->filesize() > 5242880)
          {
            return Response::json(['error' => 'Your image is too big.']);
          }

          if($avatarImg->mime() != "image/png" && $avatarImg->mime() != "image/jpeg")
          {
            return Response::json(['error' => 'Not a valid PNG/JPG image.']);
          }
          else {
            if($avatarImg->mime() == "image/png")
            {
              $ext = "png";
            }
            else if($avatarImg->mime() == "image/jpeg")
            {
              $ext = "jpg";
            }

          $avatarImg->save($imageFile.'/'.$string.'.'.$ext);

          $avatarImg = $imageFile.'/'.$string.'.'.$ext;

          $img = Image::make($avatarImg);

          list($width, $height) = getimagesize($avatarImg);
          if($width > 150)
          {
            $img->resize(150, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            if($height > 150)
            {
              $img->crop(150, 150);
            }
          }
          $img->save($avatarImg);

          if($avatarImg != NULL)
          {
            $avatarImg = $request->root().'/'.$avatarImg;
          }

          $user = User::find($user->id);
          $user->avatar = $avatarImg;
          $user->save();
        }
        else {

          $user = User::find($user->id);
          $user->avatar = $user->avatar;
          $user->save();
        }

        if($profileName == NULL)
        {
          $profileName = $user->name;
        }

        $profile->profileName = $profileName;
        //$profile->profilePhone = $profilePhone;
        //$profile->profileSocial = $profileSocial;
        if($profileLocation == NULL) {
          $profileLocation = "";
        }
        $profile->profileLocation = $profileLocation;
        if($profileDesc == NULL) {
          $profileDesc = "";
        }
        $profile->profileDesc = $profileDesc;
        //$profile->profileLanguage = $profileLanguage;
        if($profileWebsite == NULL) {
          $profileWebsite = "";
        }
        $profile->profileWebsite = $profileWebsite;
        if(!empty($newPassword))
        {
          $userObject->password = Hash::make($newPassword);
          $userObject->save();
        }
        $profile->save();

        $profileData = Profile::where('userID', '=', $user->id)->select('profileName', 'profileLocation', 'profileDesc', 'profileWebsite', 'profileTopics', 'profileVotes', 'profileReplies', 'profileScore')->first();
        return Response::json(['success' => $profileData]);
      }
      else
      {
        return Response::json(['error' => 'You do not have permission.']);
      }
    }
  }

  public function profileTopics($id)
  {
    $user = User::find($id);
    $topics = Topic::where('topics.userID', '=', $user->id)->join('users', 'topics.userID', '=', 'users.id')->where('users.ban', '=', 0)->where('users.inactive', '=', 0)->join('profiles', 'users.id', '=', 'profiles.userID')->orderBy('topics.created_at', 'DESC')->select('topics.id', 'topics.userID', 'topics.topicReplies', 'topics.topicVotes', 'topics.created_at', 'users.name', 'users.avatar', 'profiles.profileName')->paginate(12);
    foreach($topics as $tkey => $topic)
    {
      $topic->topicDate = Carbon::createFromTimeStamp(strtotime($topic->created_at))->diffForHumans();
      $vote = Vote::where('userID', '=', $user->id)->where('topicID', '=', $topic->id)->first();

      if(!empty($vote))
      {
        if($vote->vote == 1)
        {
          $topic->vote = 1;
        }
        else if($vote->vote == 0)
        {
          $topic->vote = 2;
        }
      }
      else {
        $topic->vote = 0;
      }

      $photos = Photo::where('topicID', '=', $topic->id)->get();
      $topic->topicThumbnail = $photo[0];
    }

    return Response::json($topics);
  }

  public function profileReplies($id)
  {
    $user = User::find($id);
    $replies = Reply::where('replies.userID', '=', $user->id)->join('users', 'replies.userID', '=', 'users.id')->where('users.ban', '=', 0)->select('replies.id', 'replies.replyBody', 'users.name', 'users.avatar')->get();

    return Response::json($topics);
  }

  public function profileVotes($id)
  {
    $user = User::find($id);
    $topics = Vote::where('votes.userID', '=', $user->id)->join('topics', 'votes.topicID', '=', 'topics.id')->join('users', 'topics.userID', '=', 'users.id')->where('users.ban', '=', 0)->orderBy('topics.created_at', 'DESC')->select('topics.id', 'topics.topicBody', 'topics.topicThumbnail', 'topics.topicTags', 'topics.topicReplies', 'topics.topicReplies', 'topics.topicVotes')->paginate(30);

    return Response::json($topics);
  }

  public function deactivateProfile()
  {
    $auth = Auth::user();
    $user->inactive = 1;
    $user->save();

    return Response::json(1);
  }

  public function getNotifs()
  {
    $user = Auth::user();
    $notifications = Notification::where('notifications.userID', '=', $user->id)
      ->where('notifications.notiType', '!=', 'Follower')
      ->where('notifications.notiType', '!=', 'Request')
      ->where('notifications.notiType', '!=', 'Accept')
      ->where('notifications.notiType', '!=', 'Topic Report')
      ->where('notifications.notiType', '!=', 'Reply Report')
      ->where('notifications.notiType', '!=', 'Profile Report')
      ->join('users', 'notifications.peerID', '=', 'users.id')
      ->select('notifications.id', 'notifications.topicID', 'notifications.peerID', 'notifications.notiType', 'notifications.read', 'users.name', 'users.avatar')
      ->orderBy('notifications.created_at', 'DESC')
      ->paginate(30);

    $requests = Notification::where('notifications.userID', '=', $user->id)
      ->where('notifications.notiType', '!=', 'Reply')
      ->where('notifications.notiType', '!=', 'Vote')
      ->where('notifications.notiType', '!=', 'Bounce')
      ->where('notifications.notiType', '!=', 'Mention')
      ->where('notifications.notiType', '!=', 'Topic Report')
      ->where('notifications.notiType', '!=', 'Reply Report')
      ->where('notifications.notiType', '!=', 'Profile Report')
      ->join('users', 'notifications.peerID', '=', 'users.id')
      ->select('notifications.id', 'notifications.topicID', 'notifications.peerID', 'notifications.notiType', 'notifications.read', 'users.name', 'users.avatar')
      ->orderBy('notifications.created_at', 'DESC')
      ->paginate(30);

    $reports = Notification::where('notifications.userID', '=', $user->id)
      ->where('notifications.notiType', '!=', 'Reply')
      ->where('notifications.notiType', '!=', 'Vote')
      ->where('notifications.notiType', '!=', 'Bounce')
      ->where('notifications.notiType', '!=', 'Mention')
      ->where('notifications.notiType', '!=', 'Follower')
      ->where('notifications.notiType', '!=', 'Request')
      ->where('notifications.notiType', '!=', 'Accept')
      ->join('users', 'notifications.peerID', '=', 'users.id')
      ->where('users.role', '=', 1)
      ->select('notifications.id', 'notifications.topicID', 'notifications.peerID', 'notifications.notiType', 'notifications.read', 'users.name', 'users.avatar')
      ->orderBy('notifications.created_at', 'DESC')
      ->paginate(30);

    return Response::json(['notifications' => $notifications, 'requests' => $requests, 'reports' => $reports]);
  }


  public function getNotifCount()
  {
    $user = Auth::user();
    $notifications = Notification::where('notifications.userID', '=', $user->id)->where('notifications.read', '=', 0)->count();

    return Response::json($notifications);
  }

  public function readNotifs()
  {
    $user = Auth::user();
    $notifs = Notification::where('userID', '=', $user->id)->where('read', '=', 0)->get();

    foreach($notifs as $nkey => $notif)
    {
      $notif->read = 1;
      $notif->save();
    }

    return Response::json(1);
  }

  public function deleteNotif($id)
  {
    $user = Auth::user();
    $notif = Notification::where('userID', '=', $user->id)->where('id', '=', $id)->first();
    $notif->delete();

    return Response::json(1);
  }

  public function getSettings()
  {
    $user = Auth::user();
    $settings = Setting::where('userID', '=', $user->id)->first();
    if($settings->notiVote == 1){ $settings->notiVote = true; }else { $settings->notiVote = false; }
    if($settings->notiReply == 1){ $settings->notiReply = true; }else { $settings->notiReply = false; }
    if($settings->notiBounce == 1){ $settings->notiBounce = true; }else { $settings->notiBounce = false; }
    if($settings->notiMention == 1){ $settings->notiMention = true; }else { $settings->notiMention = false; }
    if($settings->profPrivate == 1){ $settings->profPrivate = true; }else { $settings->profPrivate = false; }

    return Response::json($settings);
  }

  public function updateSettings(Request $request)
  {
    $user = Auth::user();
    $settings = Setting::where('userID', '=', $user->id)->first();

    //$settings->theme = $request->json('theme');
    //$settings->autoImg = $request->json('autoImg');
    $settings->notiVote = $request->json('notiVote');
    $settings->notiReply = $request->json('notiReply');
    $settings->notiBounce = $request->json('notiBounce');
    $settings->notiMention = $request->json('notiMention');
    //$settings->notiWeekly = $request->json('notiWeekly');
    $settings->profPrivate = $request->json('profPrivate');
    $settings->save();

    return Response::json(['success' => '']);
  }

  public function banUser($id)
  {
    $auth = Auth::user();
    if($auth->role == 1)
    {
      $user = User::find($id);
      if($user->ban == 0)
      {
        $user->ban = 1;
        $user->save();
        return Response::json(1);
      }
      else {
        $user->ban = 0;
        $user->save();
        return Response::json(0);
      }
    }
  }

  public function getUsers()
  {
    $users = User::where('ban', '=', 0)->where('inactive', '=', 0)->orderBy('updated_at', 'DESC')->select('id', 'name', 'avatar')->take(100)->get();

    return Response::json($users);
  }

}
