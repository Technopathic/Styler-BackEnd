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

class TopicsController extends Controller
{
  public function __construct()
  {
    $this->middleware('jwt.auth');
  }

  public function getTopics()
  {
    $user = Auth::user();
    $topics = Topic::join('users', 'topics.userID', '=', 'users.id')->where('users.ban', '=', 0)->where('users.inactive', '=', 0)->join('profiles', 'users.id', '=', 'profiles.userID')->orderBy('topics.created_at', 'DESC')->select('topics.id', 'topics.userID', 'topics.topicReplies', 'topics.topicReplies', 'topics.topicVotes', 'topics.created_at', 'users.name', 'users.avatar', 'profiles.profileName')->paginate(15);
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

      $photos = Photo::where('topicID', '=', $topic->id)->select('photoThumbnail', 'topicID')->get();
      foreach($photos as $pKey => $photo)
      {
        $photo->active = false;
      }

      $topic->topicThumbnail = $photos;

    }

    return Response::json($topics);
  }

  public function getHot()
  {
    $user = Auth::user();
    $topics = Topic::join('users', 'topics.userID', '=', 'users.id')->where('users.ban', '=', 0)->where('users.inactive', '=', 0)->join('profiles', 'users.id', '=', 'profiles.userID')->orderBy('topics.topicVotes', 'DESC')->select('topics.id', 'topics.userID', 'topics.topicReplies', 'topics.topicReplies', 'topics.topicVotes', 'topics.created_at', 'users.name', 'users.avatar', 'profiles.profileName')->paginate(15);

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

      $photos = Photo::where('topicID', '=', $topic->id)->select('photoThumbnail', 'topicID')->get();
      foreach($photos as $pKey => $photo)
      {
        $photo->active = false;
      }

      $topic->topicThumbnail = $photos;
    }

    return Response::json($topics);
  }

  public function getFeature()
  {
    $user = Auth::user();
    $topics = Topic::join('users', 'topics.userID', '=', 'users.id')->where('topics.topicFeature', '=', 1)->where('users.ban', '=', 0)->join('profiles', 'users.id', '=', 'profiles.userID')->where('users.inactive', '=', 0)->orderBy('topics.created_at', 'DESC')->select('topics.id', 'topics.userID', 'topics.topicThumbnail', 'topics.', 'topics.topicReplies', 'topics.topicReplies', 'topics.topicVotes', 'topics.created_at', 'users.name', 'users.avatar', 'profiles.profileName')->paginate(30);

    foreach($topics as $tkey => $topic)
    {
      $topic->topicDate = Carbon::createFromTimeStamp(strtotime($topic->created_at))->diffForHumans();
      $vote = Vote::where('userID', '=', $user->id)->where('topicID', '=', $topic->id)->first();

      if(!empty($votes))
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
    }

    return Response::json($topics);
  }

  public function storeTopic(Request $request)
  {
    $rules = array(
      'topicImg'		=> 	'required'
    );
    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        return Response::json(['error' => 'Please fill out all fields.']);
    } else {

      $userID = Auth::user();
      $topicReplies = 0;
      $topicVotes = 0;

      $topic = new Topic;

      $articles = $request->input('topicArticles');
      if(count($articles) > 10)
      {
        return Response::json(['error' => 'Sorry, but you may only have 10 items.']);
      }

      $user = User::find($userID->id);
      if($user->ban == 1 || $user->inactive == 1)
      {
        return Response::json(2);
      }

      $current = Carbon::now();
      $checkTopic = Topic::where('userID', '=', $user->id)->orderBy('created_at', 'DESC')->select('created_at')->first();
      if(!empty($checkTopic))
      {
        $topicTime  = Carbon::createFromTimeStamp(strtotime($checkTopic->created_at));
        if($current->diffInMinutes($topicTime) < 5)
        {
          return Response::json(['error' => "Slow down, you are posting way too fast."]);
        }
      }

      $imageFile = 'storage/media/topics/image';
      if (!is_dir($imageFile)) {
        mkdir($imageFile,0777,true);
      }
      $thumbnailFile = 'storage/media/topics/image/thumbnails';
      if (!is_dir($thumbnailFile)) {
        mkdir($thumbnailFile,0777,true);
      }

      $string = str_random(15);
      $imgArray = $request->input('topicImg');
      foreach($imgArray as $tKey => $imgItem)
      {
        $topicImg = Image::make($imgItem);

        if($topicImg->filesize() > 5242880)
        {
          return Response::json(['error' => 'One of your images was too large.']);
        }

        if($topicImg->mime() != "image/png" && $topicImg->mime() != "image/jpeg")
        {
          return Response::json(['error' => 'Not a valid PNG/JPG/GIF image.']);
        }
        else {
          if($topicImg->mime() == "image/png")
          {
            $ext = "png";
          }
          else if($topicImg->mime() == "image/jpeg")
          {
            $ext = "jpg";
          }
        }

        $topicImg->save($imageFile.'/'.$string.'.'.$ext);
        $topicImg = $imageFile.'/'.$string.'.'.$ext;

        $topicThumbnail = $thumbnailFile.'/'.$string.'_thumbnail.png';
        $img = Image::make($topicImg);

        list($width, $height) = getimagesize($topicImg);
        if($width > 500)
        {
          $img->resize(500, null, function ($constraint) {
              $constraint->aspectRatio();
          });
          if($height > 300)
          {
            $img->crop(500, 300);
          }
        }
        $img->save($topicThumbnail);

        if($topicImg != NULL)
        {
          $topicImg = $request->root().'/'.$topicImg;
        }

        if($topicThumbnail != NULL)
        {
          $topicThumbnail = $request->root().'/'.$topicThumbnail;
        }

        $photo = new Photo;
        $photo->userID = $user->userID;
        $photo->topicID = $topic->id;
        $photo->photoImg = $topicImg;
        $photo->photoThumbnail = $topicThumbnail;
        $photo->save();
      }

      $topic->userID = $user->id;
      $topic->topicReplies = $topicReplies;
      $topic->topicVotes = $topicVotes;
      $topic->save();

      $profile = Profile::where('userID', '=', $user->id)->first();
      $profile->profileTopics = $profile->profileTopics + 1;
      $profile->profileScore = $profile->profileScore + 10;
      $profile->save();

      foreach($articles as $aKey => $article)
      {
        $item = new Item;
        $item->userID = $user->id;
        $item->topicID = $topic->id;
        $item->itemType = $article->itemType;
        $item->itemBrand = $article->itemBrand;
        $item->itemName = $article->itemName;
        $item->itemLink = $article->itemLink;
        $item->itemSize = $article->itemSize;
        $item->itemCoords = "0, 0";
        $item->save();
      }

      $topicMentions = json_decode($topicMentions, true);
      if(!empty($topicMentions))
      {
        if(count($topicMentions) > 3)
        {
          return Response::json(3);
        }
        else {
          foreach($topicMentions as $mkey => $mention)
          {
            $mUser = User::find($mention['id']);
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



      $topicData = Topic::where('topics.id', '=', $topic->id)->join('users', 'topics.userID', '=', 'users.id')->select('topics.id', 'topics.topicReplies', 'topics.topicVotes', 'users.name')->first();
      return Response::json($topicData);
    }
  }

  public function showTopic($id)
  {
    $user = Auth::user();
    $topic = Topic::where('topics.id', '=', $id)->join('users', 'topics.userID', '=', 'users.id')->where('users.ban', '=', 0)->join('profiles', 'users.id', '=', 'profiles.userID')->select('topics.id', 'topics.userID', 'topics.topicReplies', 'topics.topicVotes', 'topics.topicFlag', 'topics.created_at', 'users.name', 'users.avatar', 'profiles.profileName')->first();
    $vote = Vote::where('userID', '=', $user->id)->where('topicID', '=', $topic->id)->first();
    $photos = Photo::where('topicID', '=', $topic->id)->select('photoThumbnail', 'photoImg', 'topicID')->get();
    $topic->topicDate = Carbon::createFromTimeStamp(strtotime($topic->created_at))->diffForHumans();

    if(!empty($vote))
    {
      if($vote->vote == 1)
      {
        $topic->vote = 1;
      }
      else {
        $topic->vote = 0;
      }
    }
    else {
      $topic->vote = 0;
    }

    $topic->topicThumbnail = $photos;

    return Response::json($topic);
  }

  public function updateTopic(Request $request, $id)
  {
    $rules = array(
      'topicImg'		=> 	'required'
    );
    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        return Response::json(0);
    } else {

      $topic = Topic::find($id);

      $userID = Auth::user();

      $user = User::find($userID->id);
      if($user->ban == 1)
      {
        return Response::json(['error' => 'You were banned.']);
      }

      $articles = $request->input('topicArticles');
      if(count($articles) > 10)
      {
        return Response::json(['error' => 'Sorry, but you may only have 10 items.']);
      }

      if($user->id == $topic->userID || $user->role == 1)
      {
        $imageFile = 'storage/media/topics/image';
        if (!is_dir($imageFile)) {
          mkdir($imageFile,0777,true);
        }
        $thumbnailFile = 'storage/media/topics/image/thumbnails';
        if (!is_dir($thumbnailFile)) {
          mkdir($thumbnailFile,0777,true);
        }

        $string = str_random(15);
        $imgArray = $request->input('topicImg');
        foreach($imgArray as $tKey => $imgItem)
        {
          $topicImg = Image::make($imgItem);

          if($topicImg->filesize() > 5242880)
          {
            return Response::json(['error' => 'One of your images was too large.']);
          }

          if($topicImg->mime() != "image/png" && $topicImg->mime() != "image/jpeg")
          {
            return Response::json(['error' => 'Not a valid PNG/JPG/GIF image.']);
          }
          else {
            if($topicImg->mime() == "image/png")
            {
              $ext = "png";
            }
            else if($topicImg->mime() == "image/jpeg")
            {
              $ext = "jpg";
            }
          }

          $topicImg->save($imageFile.'/'.$string.'.'.$ext);
          $topicImg = $imageFile.'/'.$string.'.'.$ext;

          $topicThumbnail = $thumbnailFile.'/'.$string.'_thumbnail.png';
          $img = Image::make($topicImg);

          list($width, $height) = getimagesize($topicImg);
          if($width > 500)
          {
            $img->resize(500, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            if($height > 300)
            {
              $img->crop(500, 300);
            }
          }
          $img->save($topicThumbnail);

          if($topicImg != NULL)
          {
            $topicImg = $request->root().'/'.$topicImg;
          }

          if($topicThumbnail != NULL)
          {
            $topicThumbnail = $request->root().'/'.$topicThumbnail;
          }

          $photo = new Photo;
          $photo->userID = $user->userID;
          $photo->topicID = $topic->id;
          $photo->photoImg = $topicImg;
          $photo->photoThumbnail = $topicThumbnail;
          $photo->save();
        }

        $topicData = Topic::where('topics.id', '=', $topic->id)->join('users', 'topics.userID', '=', 'users.id')->select('topics.id', 'topics.topicReplies', 'topics.topicVotes', 'users.name')->first();
        return Response::json($topicData);
      }
      else {
        return Response::json(['error' => 'You do not have permission to do this.']);
      }
    }
  }

  public function deleteTopic($id)
  {
    $topic = Topic::find($id);
    $user = Auth::user();

    if($topic->userID == $user->id || $user->role == 1)
    {
      $photos = Photo::where('topicID', '=', $topic->id)->get();
      foreach($photos as $pKey => $photo)
      {
        $photo->delete();
      }

      $topic->delete();

      $profile = Profile::where('userID', '=', $user->id)->first();
      $profile->profileTopics = $profile->profileTopics + 1;
      $profile->profileScore = $profile->profileScore - 10;
      $profile->save();

      return Response::json(['success' => 'Topic was deleted.']);
    }

    else
    {
      return Response::json(['error' => 'You do not have permission to do this.']);
    }
  }

  public function voteTopic(Request $request, $id)
  {
    $rules = array(
      'dir'		=> 	'required'
    );
    $validator = Validator::make($request->json()->all(), $rules);

    if ($validator->fails()) {
        return Response::json(0);
    } else {

      $dir = $request->json('dir');
      $topic = Topic::find($id);
      $user = Auth::user();
      $check = Vote::where('topicID', '=', $topic->id)->where('userID', '=', $user->id)->first();
      if(empty($check))
      {
        if($dir == 1)
        {
          $topic->topicVotes = $topic->topicVotes + 1;
          $topic->save();

          $row = new Vote;
          $row->userID = $user->id;
          $row->topicID = $topic->id;
          $row->vote = 1;
          $row->save();

          $profile = Profile::where('userID', '=', $user->id)->first();
          $profile->profileVotes = $profile->profileVotes + 1;
          $profile->profileScore = $profile->profileScore + 1;
          $profile->save();

          $settings = Setting::where('userID', '=', $topic->userID)->first();
          if($settings->notiVote == 1)
          {
            $notiCheck = Notification::where('userID', '=', $topic->userID)->where('peerID', '=', $user->id)->where('topicID', '=', $topic->id)->where('notiType', '=', 'Vote')->first();
            if(empty($notiCheck))
            {
              if($topic->userID != $user->id)
              {
                $notif = new Notification;
                $notif->userID = $topic->userID;
                $notif->topicID = $topic->id;
                $notif->peerID = $user->id;
                $notif->notiType = "Vote";
                $notif->save();
              }
            }
          }

          return Response::json(1);
        }
        else {
          $topic->topicVotes = $topic->topicVotes - 1;
          $topic->save();

          $row = new Vote;
          $row->userID = $user->id;
          $row->topicID = $topic->id;
          $row->vote = 0;
          $row->save();

          $profile = Profile::where('userID', '=', $user->id)->first();
          $profile->profileVotes = $profile->profileVotes + 1;
          $profile->profileScore = $profile->profileScore + 1;
          $profile->save();

          return Response::json(3);
        }
      } else {
        if($dir == 1 && $check->vote == 1)
        {
          $topic->topicVotes = $topic->topicVotes - 1;
          $topic->save();

          $profile = Profile::where('userID', '=', $user->id)->first();
          $profile->profileVotes = $profile->profileVotes - 1;
          $profile->profileScore = $profile->profileScore - 1;
          $profile->save();

          $check->delete();

          return Response::json(2);
        }
        else if($dir == 0 && $check->vote == 0)
        {
          $topic->topicVotes = $topic->topicVotes + 1;
          $topic->save();

          $profile = Profile::where('userID', '=', $user->id)->first();
          $profile->profileVotes = $profile->profileVotes - 1;
          $profile->profileScore = $profile->profileScore - 1;
          $profile->save();

          $check->delete();

          return Response::json(4);
        }
        else if($dir == 1 && $check->vote == 0)
        {
          $topic->topicVotes = $topic->topicVotes + 1;
          $topic->save();

          $profile = Profile::where('userID', '=', $user->id)->first();
          $profile->profileVotes = $profile->profileVotes - 1;
          $profile->profileScore = $profile->profileScore - 1;
          $profile->save();

          $check->delete();

          return Response::json(4);
        }
        else if($dir == 0 && $check->vote == 1)
        {
          $topic->topicVotes = $topic->topicVotes - 1;
          $topic->save();

          $profile = Profile::where('userID', '=', $user->id)->first();
          $profile->profileVotes = $profile->profileVotes - 1;
          $profile->profileScore = $profile->profileScore - 1;
          $profile->save();

          $check->delete();

          return Response::json(2);
        }
      }
    }
  }

  public function favTopic(Request $request, $id)
  {
    $topic = Topic::find($id);
    $user = Auth::user();
    $check = Vote::where('topicID', '=', $topic->id)->where('userID', '=', $user->id)->first();
    if(empty($check))
    {
      $topic->topicVotes = $topic->topicVotes + 1;
      $topic->save();

      $row = new Vote;
      $row->userID = $user->id;
      $row->topicID = $topic->id;
      $row->vote = 1;
      $row->save();

      $profile = Profile::where('userID', '=', $user->id)->first();
      $profile->profileVotes = $profile->profileVotes + 1;
      $profile->profileScore = $profile->profileScore + 1;
      $profile->save();

      $settings = Setting::where('userID', '=', $topic->userID)->first();
      if($settings->notiVote == 1)
      {
        $notif = new Notification;
        $notif->userID = $topic->userID;
        $notif->topicID = $topic->id;
        $notif->peerID = $user->id;
        $notif->notiType = "Vote";
        $notif->save();
      }

      return Response::json(1);
    } else {
      $topic->topicVotes = $topic->topicVotes - 1;
      $topic->save();

      $profile = Profile::where('userID', '=', $user->id)->first();
      $profile->profileVotes = $profile->profileVotes - 1;
      $profile->profileScore = $profile->profileScore - 1;
      $profile->save();

      $check->delete();

      return Response::json(0);
    }
  }


  public function getVoters($id)
  {
    $votes = Vote::where('votes.topicID', '=', $id)->where('votes.vote', '=', 1)->join('users', 'votes.userID', '=', 'users.id')->where('users.ban', '=', 0)->where('users.inactive', '=', 0)->select('votes.id', 'users.name', 'users.avatar')->paginate(30);

    return Response::json($votes);
  }

  public function reportTopic($id)
  {
    $user = Auth::user();
    if(empty($user))
    {
      return Response::json(0);
    }
    else {
      $topic = Topic::find($id);
      if($topic->userID == $user->id)
      {
        return Response::json(2);
      }

      $topic->topicFlag = 1;
      $topic->save();

      $admins = User::where('role', '=', 1)->where('ban', '=', 0)->where('inactive', '=', 0)->get();
      foreach($admins as $akey => $admin)
      {
        $notif = new Notification;
        $notif->userID = $admin->id;
        $notif->topicID = $topic->id;
        $notif->peerID = $user->id;
        $notif->notiType = "Topic Report";
        $notif->save();
      }

      return Response::json(1);
    }
  }

  public function unReportTopic($id)
  {
    $user = Auth::user();
    if(empty($user))
    {
      return Response::json(0);
    }
    else {
      if($user->role == 1)
      {
        $topic = Topic::find($id);
        $topic->topicFlag = 0;
        $topic->save();

        return Response::json(1);
      }
      else {
        return Response::json(0);
      }
    }
  }

  public function setFeature($id)
  {
    $user = Auth::user();
    if($user->role == 1)
    {
      $topic = Topic::find($id);
      if($topic->topicFeature == 1)
      {
        $topic->topicFeature = 0;
        $topic->save();

        return Response::json(1);
      }
      else
      {
        $topic->topicFeature = 1;
        $topic->save();

        return Response::json(0);
      }
    }
  }

  public function getReported()
  {
    $user = Auth::user();
    if($user->role == 1)
    {
      $topics = Topic::join('users', 'topics.userID', '=', 'users.id')->where('topics.topicsFlag', '=', 1)->where('users.ban', '=', 0)->orderBy('topics.created_at', 'DESC')->select('topics.id', 'topics.topicThumbnail', 'topics.topicReplies', 'topics.topicReplies', 'topics.topicVotes')->paginate(30);

      return Response::json($topics);
    }
  }

  public function toggleFlag($id)
  {
    $user = Auth::user();
    if($user->role == 1)
    {
      $topic = Topic::find($id);
      if($topic->topicFlag == 1)
      {
        $topic->topicFlag = 0;
        $topic->save();

        return Response::json(1);
      }
      else
      {
        $topic->topicFlag = 1;
        $topic->save();

        return Response::json(0);
      }
    }
  }

}
