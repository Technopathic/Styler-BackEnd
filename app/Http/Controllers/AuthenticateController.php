<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\User;
use App\Role;
use App\Profile;
use App\Setting;
use \Response;
use \Auth;
use \DB;
use \Mail;
use DateTime;
use Redirect;
use Session;
use Socialite;

class AuthenticateController extends Controller
{

  public function __construct()
  {
    $this->middleware('jwt.auth', ['except' => ['signIn', 'getAuthenticatedUser', 'signUp', 'socialSignOn', 'resetPassword', 'confirmReset', 'refreshToken', 'getReset', 'resetComplete']]);
  }

  public function index()
  {

  }

  public function signUp(Request $request)
  {
    $rules = array(
      'email'	        => 	'required',
      'username'			=>	'required',
      'password'			=>	'required'
    );
    $validator = Validator::make($request->json()->all(), $rules);

    if ($validator->fails()) {
      return Response::json(['error' => 'Fill out all fields.']);
    } else {

      $email = $request->json('email');
      $username = $request->json('username');
      $fullName = $request->json('fullName');
      $password = $request->json('password');

      $email = strtolower($email);
      $username = strtolower($username);
      $username = preg_replace('/[^0-9A-Z]/i', "" ,$username);
      $sub = substr($username, 0, 2);

      if(empty($fullName))
      {
        $fullName = $username;
      }

      $userCheck = User::where('email', '=', $email)->orWhere('name', '=', $username)->select('email', 'name')->first();

      if(empty($userCheck))
      {

        $role = Role::find(2);

        $user = new User;
        $user->email = $email;
        $user->name = $username;
        $user->password = Hash::make($password);
        $user->avatar = "https://invatar0.appspot.com/svg/".$sub.".jpg?s=100";
        $user->role = $role->id;
        $user->provider = 'Native';
        $user->save();

        $profile = new Profile;
        $profile->userID = $user->id;
        $profile->profileName = $username;
        $profile->profileTopics = 0;
        $profile->profileVotes = 0;
        $profile->profileReplies = 0;
        $profile->profileScore = 0;
        $profile->save();

        $settings = new Setting;
        $settings->userID = $user->id;
        $settings->save();

        return Response::json(1);

      } else {
        if($userCheck->email === $email)
        {
          //Email Already Registered
          return Response::json(['error' => 'E-mail is already registered.']);
        }
        elseif($userCheck->name === $username)
        {
          //Username already Registered
          return Response::json(['error' => 'Username is already registered.']);
        }
      }
    }
  }

  public function signIn(Request $request)
  {
      $email = $request->json('email');
      $password = $request->json('password');
      $hash = Hash::make($password);
      $userCheck = User::where('email', '=', $email)->where('password', '!=', NULL)->first();
      if(!empty($userCheck))
      {
        $cred = array("email", "password");
        $credentials = compact("email", "password", $cred);
        try {
          if (! $token = JWTAuth::attempt($credentials)) {
              return response()->json(['error' => 'invalid_credentials'], 401);
          }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }
        if($userCheck->ban == 1) {
          //User is banned
          return Response::json(0);
        }
        else {
          return Response::json(compact('token'));
        }
      } else {
        //User not found
        return Response::json(2);
      }
  }

  public function getAuthenticatedUser()
  {
      try {
        if (! $user = JWTAuth::parseToken()->authenticate()) {
          return response()->json(['user_not_found'], 404);
        }
      } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
        return response()->json(['token_expired'], $e->getStatusCode());
      } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
        return response()->json(['token_invalid'], $e->getStatusCode());
      } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {
        return response()->json(['token_absent'], $e->getStatusCode());
      }

      $user = compact('user');
      $profile = Profile::where('userID', '=', $user['user']->id)->select('profileName')->first();
      return Response::json(['user' => $user, 'profile' => $profile]);
  }

  public function refreshToken(Request $request) {
    $token = JWTAuth::getToken();
    if(!$token){
        throw new BadRequestHtttpException('Token not provided');
    }
    try{
        $token = JWTAuth::refresh($token);
    }catch(TokenInvalidException $e){
        throw new AccessDeniedHttpException('The token is invalid');
    }

    return Response::json($token);
  }

  public function resetPassword(Request $request)
  {
    $rules = array(
      'email'		=> 	'required|email'
    );
    $validator = Validator::make($request->json()->all(), $rules);

    if ($validator->fails()) {
        return Response::json(0);
    } else {

      $resetId = $request->json('email');

      if(filter_var($resetId, FILTER_VALIDATE_EMAIL)) {
        $user = User::where('email', '=', $resetId)->where('password', '!=', NULL)->select('email', 'name')->first();
        if(!empty($user))
        {
          $token = str_random(32);
          $website = "Bragger";
          $check = DB::table('password_resets')->where('email', '=', $user->email)->first();
          if(!empty($check))
          {
            $check = DB::table('password_resets')->where('email', '=', $user->email)->delete();
          }

          DB::table('password_resets')->insert(array('email' => $user->email, 'token' => $token));
          Mail::send('emails.reset', ['user' => $user, 'token' => $token, 'website' => $website], function ($m) use ($user, $token, $website) {
              $m->from("no-reply@technopathic.me", "Bragger");
              $m->to($user->email)->subject($website.' - Password Reset');
          });

          return Response::json(1);
        }
        else {
          return Response::json(2);
        }
      }
      /*else {
        $user = User::where('name', '=', $resetId)->select('email', 'name')->first();
        if(!empty($user))
        {
          $token = Hash::make($user->email);
          $website = "devBrag";
          DB::table('password_resets')->insert(array('email' => $user->email, 'token' => $token));
          Mail::send('emails.resetPassword', ['user' => $user, 'token' => $token, 'website' => $website], function ($m) use ($user, $token, $website) {
              $m->to($user->email)->subject($website.' - Password Reset');
          });

          return Response::json(1);
        }
        else {
          return Response::json(3)->setCallback($request->input('callback'));
        }
      }*/
    }
  }

  public function getReset()
  {
    return view('reset');
  }

  public function resetComplete()
  {
    return view('resetComplete');
  }

  public function confirmReset(Request $request, $token)
  {
    $rules = array(
      'password'		=> 	'required',
      'confirm' => 'required'
    );
    $validator = Validator::make($request->all(), $rules);
    if ($validator->fails()) {
        Session::flash('message', 'Please fill out the fields.');
        return Redirect::to('reset?token='.$token);
    } else {

      $reset = DB::table('password_resets')->where('token', '=', $token)->first();
      if(empty($reset))
      {
        Session::flash('message', 'Token Not Found');
        return Redirect::to('resetComplete');
      }
      else {
        $date1 = new DateTime($reset->created_at);
        $date2 = new DateTime();

        $diff = $date2->diff($date1);

        $hours = $diff->h;
        $hours = $hours + ($diff->days*24);

        if($hours > 24)
        {
          //This reset form has expired.
          $reset = DB::table('password_resets')->where('token', '=', $token)->delete();
          Session::flash('message', 'This reset form is expired.');
          return Redirect::to('resetComplete');
        }
        else {
          $newPassword = $request->input('password');
          $confirmPassword = $request->input('confirm');

          if($newPassword != $confirmPassword)
          {
            //Passwords do not match.
            Session::flash('message', 'Your passwords did not match.');
            return Redirect::to('reset?token='.$token);
          }
          else {
            $user = User::where('email', '=', $reset->email)->first();

            $user->password = Hash::make($newPassword);
            $user->save();

            $reset = DB::table('password_resets')->where('token', '=', $token)->delete();

            Session::flash('message', 'Successfully reset password.');
            return Redirect::to('resetComplete');
          }
        }
      }
    }
  }

  public function socialSignOn(Request $request)
  {
    $rules = array(
      'token'         =>  'required',
      'provider'      =>  'required'
    );
    $validator = Validator::make($request->json()->all(), $rules);

    if ($validator->fails()) {
      return Response::json(['error' => 'Please Fill out all Fields.']);
    } else {

      $provider = $request->json('provider');
      $token = $request->json('token');
      $secret = $request->json('secret');

      if($provider == "Twitter")
      {
        $social = Socialite::driver($provider)->userFromTokenAndSecret($token, $secret);
      }
      else {
        $social = Socialite::driver($provider)->userFromToken($token);
      }

      $email = strtolower($social->getEmail());
      $username = strtolower($social->getName());
      $username = preg_replace('/[^0-9A-Za-z]/i', "" ,$username);
      if (strlen($username) > 12)
      {
        $username = substr($username, 0, 12);
      }
      $sub = substr($username, 0, 2);
      $uid = $social->getId();

      $check = User::where('email', '=', $email)->first();
      if(empty($check))
      {
        $nameCheck = User::where('name', '=', $username)->select('name')->first();
        if(!empty($nameCheck))
        {
          $str = str_random(4);
          $username = $username.$str;
        }
        $role = Role::find(2);

        $user = new User;
        $user->email = $email;
        $user->name = $username;
        $user->password = Hash::make($uid.$username.$email.$token);
        $user->avatar = "https://invatar0.appspot.com/svg/".$sub.".jpg?s=100";
        $user->provider = $provider;
        $user->role = $role->id;
        $user->save();

        $profile = new Profile;
        $profile->userID = $user->id;
        $profile->profileName = $username;
        $profile->profileTopics = 0;
        $profile->profileVotes = 0;
        $profile->profileReplies = 0;
        $profile->profileScore = 0;
        $profile->save();

        $settings = new Setting;
        $settings->userID = $user->id;
        $settings->save();

        $check = User::find($user->id);
      }
      else {
        if($check->provider != $provider)
        {
          return Response::json(['error' => 'This E-mail was already registered.']);
        }
      }

      if($check->ban == 1) {
        return Response::json(['error' => 'Looks like you were banned.']);
      }

      try {
        if (! $token = JWTAuth::fromUser($check)) {
          return response()->json(['error' => 'invalid_credentials'], 401);
        }
      } catch (JWTException $e) {
          return response()->json(['error' => 'could_not_create_token'], 500);
      }
      return Response::json(compact('token'));
    }
  }


  public function redirectToFacebook()
  {
    return Socialite::driver('facebook')->redirect();
  }

  public function handleFacebookCallback()
  {
    $user = Socialite::driver('facebook')->stateless()->user();

    $check = User::where('email', '=', $user->getEmail())->first();
    if(empty($check))
    {
      return Response::json(['response' => 2, 'email' => $user->getEmail(), 'name' => $user->getName()]);
    }
    else {
      try {
        if (! $token = JWTAuth::fromUser($user)) {
          return response()->json(['error' => 'invalid_credentials'], 401);
        }
      } catch (JWTException $e) {
          return response()->json(['error' => 'could_not_create_token'], 500);
      }
      if($check->ban == 1) {
        //User is banned
        return Response::json(0);
      }
      else {
        return Response::json(compact('token'));
      }
    }
  }

  public function redirectToTwitter()
  {
    return Socialite::driver('twitter')->redirect();
  }

  public function handleTwitterCallback()
  {
    $user = Socialite::driver('twitter')->stateless()->user();

    $check = User::where('email', '=', $user->getEmail())->first();
    if(empty($check))
    {
      return Response::json(['response' => 2, 'email' => $user->getEmail(), 'name' => $user->getName()]);
    }
    else {
      try {
        if (! $token = JWTAuth::fromUser($user)) {
          return response()->json(['error' => 'invalid_credentials'], 401);
        }
      } catch (JWTException $e) {
          return response()->json(['error' => 'could_not_create_token'], 500);
      }
      if($check->ban == 1) {
        //User is banned
        return Response::json(0);
      }
      else {
        return Response::json(compact('token'));
      }
    }
  }

  public function redirectToGoogle()
  {
    return Socialite::driver('google')->redirect();
  }

  public function handleGoogleCallback()
  {
    $user = Socialite::driver('google')->stateless()->user();

    $check = User::where('email', '=', $user->getEmail())->first();
    if(empty($check))
    {
      return Response::json(['response' => 2, 'email' => $user->getEmail(), 'name' => $user->getName()]);
    }
    else {
      try {
        if (! $token = JWTAuth::fromUser($user)) {
          return response()->json(['error' => 'invalid_credentials'], 401);
        }
      } catch (JWTException $e) {
          return response()->json(['error' => 'could_not_create_token'], 500);
      }
      if($check->ban == 1) {
        //User is banned
        return Response::json(0);
      }
      else {
        return Response::json(compact('token'));
      }
    }
  }
}
