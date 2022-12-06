<?php

namespace App\Http\Controllers;  
use App\Models\Post;
use App\Models\Reply;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Cviebrock\EloquentSluggable\Services\SlugService;


class PostsController extends Controller
{
    public function __construct(){
        $this->middleware('auth',['except'=>['index','show']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('blog.index')
        ->with('posts',Post::orderBy('updated_at','Desc')->get());
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('blog.create');

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'=>'required',
            'description'=>'required',
            'image'=>'required|mimes:jpg,png,jpeg|max:5048'
        ]);
        $newImageName = uniqid(). '-'. $request->title.'.'.$request->image->extension();
        $request->image->extension();
        $request->image->move(public_path('images'), $newImageName);

        Post::create([
            'title'=>$request->input('title'),
            'description'=>$request->input('description'),
            'slug'=>SlugService::createSlug(Post::class,'slug',$request->title),
            'image_path'=> $newImageName,
            'user_id'=>auth()->user()->id
        ]);
        return redirect('blog')->with('message','Your post has been added');
       
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        return view('blog.show')->with('post',Post::where('slug',$slug)->first());
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($slug)
    {
        return view('blog.edit')->with('post',Post::where('slug',$slug)->first());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $slug
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $request->validate([
            'title'=>'required',
            'description'=>'required',
        ]);

        Post::where('slug',$slug)
        ->update([
            'title'=>$request->input('title'),
            'description'=>$request->input('description'),
            'slug'=>SlugService::createSlug(Post::class,'slug',$request->title),
            'user_id'=>auth()->user()->id
        ]);
        return redirect('/blog')->with('message','Your post has been updated!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        $post=Post::where('slug',$slug);
        $post->delete();

        return redirect('/blog')
        ->with('message','Your post has been deleted!');

    }

    public function likePost($post)
    {
        //check if user already liked the post or not
        $user = Auth::user();
        $likePost = $user->likedPosts()->where('post_id', $post)->count();
       if($likePost == 0){
            $user->likedPosts()->attach($post);
       }else{
        $user->likedPosts()->detach($post);
       }
        return redirect()->back();
    }

    public function add_comment(Request $request){
        if(Auth::id()){

            $comment = new comment;
            $comment->name = Auth::user()->name;
            $comment->user_id = Auth::user()->id;
            $comment->comment = $request->comment;

            $comment->save();
            return redirect()->back();
        }
        else{
            return redirect('login');
        }
    }

    public function add_reply(Request $request){
        if(Auth::id()){
            $reply = new reply;
            $reply->name = Auth::user()->name;
            $reply->comment_id = $request->commentId;
            $reply->reply = $request->reply;
            $reply->user_id = Auth::user()->id;
            $reply->save();
            return redirect()->back();

        }else{
            return redirect('login');
        }

    }
    public function delete_reply($id)
    {
        $post=Reply::where('id',$id);
        $post->delete();

        return redirect()->back()->with('message','Your reply has been deleted!');

    }
    public function delete_comment($id)
    {
        $post=Comment::where('id',$id);
        $post->delete();

        return redirect()->back()->with('message','Your reply has been deleted!');

    }


}
