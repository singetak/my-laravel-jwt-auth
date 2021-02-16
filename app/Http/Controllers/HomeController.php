<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HomeController extends Controller
{
      public function __construct() {
          // $this->middleware('auth:api');
      }
      /**
       * Display a listing of the resource.
       *
       * @return \Illuminate\Http\Response
       */
      public function index(Request $request)
      {
          $users = User::where('type', 'customer'); // make sure to return only customers
          $per_page = 15;
          $page = 1;


          if($request->per_page) {
              $per_page = $request->per_page;
          }
          if($request->page) {
              $page = $request->page;
          }
          if($request->email_like) {
              $users = $users->where('email', 'like', "%" . $request->email_like . "%");
          }
          if($request->name_like) {
              $users = $users->where('name', 'like', "%" . $request->name_like . "%");
          }
          if($request->id_like) {
              $users = $users->where('id', 'like', "%" . $request->id_like . "%");
          }
          $sortBy = 'created_at';
          if($request->orderBy) {
            $sortBy = $request->orderBy;
          }
          $sortOrder = 'asc';
          if($request->order) {
            $sortOrder = $request->order;
          }
          return response()->json($users->orderBy($sortBy, $sortOrder)->paginate($per_page, ['*'], 'page', $page));
      }
      /**
       * Display an average of the customers.
       *
       * @return \Illuminate\Http\Response
       */
      public function customerAverages(Request $request)
      {
          if($request->period) {
            $period = $request->period;
            $data = DB::table('users');
            if($period === 'alltime'){
              $data = $data->select(DB::raw('count(*) as count, YEAR(created_at) as year'))
              ->groupBy('year');
            }else if ($period === 'day'){
              $data = $data->select(DB::raw('count(*) as count, HOUR(created_at) as hour'))
              ->whereDate('created_at', '>=', Carbon::now()->subDay()->toDateString())
              ->groupBy('hour');
            }else if ($period === 'week'){
              $data = $data->select(DB::raw('count(*) as count, DAY(created_at) as day'))
              ->whereDate('created_at', '>=', Carbon::now()->subWeek()->toDateString())
              ->groupBy('day');
            }else if ($period === 'month'){
              $data = $data->select(DB::raw('count(*) as count, WEEK(created_at) as week'))
              ->whereDate('created_at', '>=', Carbon::now()->subMonth()->toDateString())
              ->groupBy('week');
            }else if ($period === '3month'){
              $data = $data->select(DB::raw('count(*) as count, MONTH(created_at) as month'))
              ->whereDate('created_at', '>=', Carbon::now()->subMonth(3)->toDateString())
              ->groupBy('month');
            }else if ($period === 'year'){
              $data = $data->select(DB::raw('count(*) as count, YEAR(created_at) as year'))
              ->whereDate('created_at', '>=', Carbon::now()->subYear()->toDateString())
              ->groupBy('year');
            }
            return response()->json([
              'data' => $data->get(),
              'status' => true,
            ]);
          }else{
            return response()->json([
              'message' => 'Data Missing!',
              'status' => false,
            ], 400);
          }


      }

      /**
       * Display the specified resource.
       *
       * @param  int  $id
       * @return \Illuminate\Http\Response
       */
      public function show($id)
      {
          $user = User::find($id);

          return response()->json($user);
      }

      /**
       * Add the specified resource in storage.
       *
       * @param  \Illuminate\Http\Request  $request
       * @param  User  $user
       * @return \Illuminate\Http\Response
       */
      public function store(Request $request)
      {
        $validator = Validator::make($request->all(), [
              'name' => 'required|string|between:2,100',
              'email' => 'required|email',
              // 'type' => 'required|between:3,30',
              'password' => 'required|string|min:6',
              // 'image' => 'sometimes|image',
          ]);

          // $user = User::create($request->all());
          $user = new User();
          $user->fill($request->all());
          // if($request->image) {
          //     $path = $request->file('image')->store('uploads');
          //     $user->image_url = Storage::url($path);
          // }
          if ($request->has('password')) {
              $user->password = bcrypt($request->get('password'));
          }
          $user->save();
          return response()->json($user);
      }

      /**
       * Update the specified resource in storage.
       *
       * @param  \Illuminate\Http\Request  $request
       * @param  User  $user
       * @return \Illuminate\Http\Response
       */
      public function update(Request $request, User $user)
      {
          $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|email',
            // 'type' => 'required|between:3,30',
            // 'image' => 'sometimes|image',
          ]);

          $validator->sometimes('email', 'unique:users', function ($input) use ($user) {
              return strtolower($input->email) != strtolower($user->email);
          });

          if ($validator->fails()) {
              return response()->json($validator->errors());
          }

          $user->name = $request->get('name');
          $user->email = $request->get('email');
          // $user->type = $request->get('type');

          // if ($request->has('password')) {
          //     $user->password = bcrypt($request->get('password'));
          // }
          // if($request->image) {
          //     $path = $request->file('image')->store('uploads');
          //     $user->image_url = Storage::url($path);
          // }
          $user->save();


          return response()->json($user);
      }

      /**
       * Remove the specified resource from storage.
       *
       * @param  int  User $user
       * @return \Illuminate\Http\Response
       */
      public function destroy(User $user)
      {
          // do not allow deletion of administrator user
          if($user->type !== 'admin') {
              $user->forceDelete();
          }

          return response()->json([], 204);
      }
}
