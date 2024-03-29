<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = 1;

        if($request->page) {
            $page = $request->page;
        }

        $key = 'blogs_'.$page;
        $tagKey = 'blog_admin';

        $blogs = Cache::tags([$tagKey])->remember($key, 60, function () {
            return Blog::orderByDesc('id')->paginate(10);
        });

        return view('dashboard.blog.index', ['blogs' => $blogs]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.blog.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->only('title', 'description');
        $data['user_id'] = auth()->id();

        if($request->hasFile('image')) {
            $file =  $request->file('image');
            $extension =  $file->extension();
            $name =  $file->getClientOriginalName();
            $path =  Str::slug($name).'_'.time().'.'.$extension;

            Storage::disk('public')->put($path, file_get_contents($file));
            $data['image'] = $path;
        }

        $response =  Blog::create($data);

        if($response) {
            return redirect()->route('admin.blog.index');
        } else {
            return redirect()->back();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Blog $blog)
    {
        return view('dashboard.blog.show', compact('blog'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Blog $blog)
    {
        return view('dashboard.blog.edit', compact('blog'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Blog $blog)
    {
        $response = $blog->update($request->only('title', 'description'));

        if($response) {
            return redirect()->route('admin.blog.index');
        } else {
            return redirect()->back();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Blog $blog)
    {
        $pathInfo = pathinfo($blog->image);
        $filename = $pathInfo['basename'];

        if($blog->delete()) {
            Storage::disk('public')->delete($filename);

            return redirect()->to(route('admin.blog.index'));
        } else {
            return redirect()->back();
        }
    }

}
