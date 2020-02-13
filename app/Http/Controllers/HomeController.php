<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\User;
use App\Message;
use Pusher\Pusher;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        //select all user except logged in user
        //$users = User::where('id','!=',Auth::id())->get();

        //count how many message are unread from the select user
        $users = DB::select("select users.id, users.name, users.avatar, users.email, count(is_read) as unread
                             from users LEFT  JOIN  messages ON users.id = messages.from and is_read = 0 and messages.to = " . Auth::id() . "
                             where users.id != " . Auth::id() . "
                             group by users.id, users.name, users.avatar, users.email");

        return view('home',compact('users'));
    }

    public function getMessage($user_id){
      //get all messages of select user
      //get all messages of now user
      $my_id = Auth::id();

      // Make read all unread message
      Message::where(['from' => $user_id, 'to' => $my_id])->update(['is_read' => 1]);

      $messages = Message::where(function($query) use ($user_id,$my_id){
        $query->where('from',$my_id)->where('to',$user_id);
      })->orwhere(function($query) use ($user_id,$my_id){
        $query->where('from',$user_id)->where('to',$my_id);
      })->get();

      return view('messages.index',compact('messages'));
    }

    public function sendMessage(Request $request){
      $from = Auth::id();
      $to = $request->receiver_id;
      $message = $request->message;

      $data = new Message();
      $data->from = $from;
      $data->to = $to;
      $data->message = $message;
      $data->is_read = 0;
      $data->save();

      // pusher
      $options = array(
        'cluster' => 'ap3',
        'useTLS' => true
      );

      $pusher = new Pusher(
        'd98e8d4e9d783be11372',
        'bc08d507c4c4527fbd48',
        '947595',
        $options
      );

      $data = ['from' => $from, 'to' => $to]; // sending from and to user id when pressed enter
      $pusher->trigger('my-channel', 'my-event', $data);
    }
}
