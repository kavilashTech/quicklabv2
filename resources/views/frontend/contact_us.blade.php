@extends('frontend.layouts.app')

@section('content')
<style>
  .error ,.required{
    color:red;
  }
</style>
<div class="contact-us-section">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-12">
    <div class="wrapper">
    <div class="row no-gutters">
    <div class="col-lg-12 col-md-7 order-md-last d-flex align-items-stretch m-4" style="background-color: #ffffff; border-radius: 50px;">
      <div class="contact-wrap w-100 p-md-5 p-4">
        <h3 class="mb-4 text-center">Get in touch</h3>
        
      <form method="POST" id="contactForm" name="contactForm" class="contactForm" action = {{route('post.contactus')}}>
      @csrf
        <div class="row">
         <div class="col-md-12">@if(session()->has('success'))
        <div class="alert alert-success">
            {{ session()->get('success') }}
        </div>
        @endif
       @if(session()->has('error'))
      <div class="alert alert-danger">
        {{ session()->get('error') }}
       </div>
    @endif</div>
        <div class="col-md-6">
          <div class="form-group">
            <label class="label" for="name">First Name<span class="required">*</span></label>
            <input type="text" class="form-control"  value="{{old('first_name')}}" name="first_name"  placeholder="First Name" >
            @error('first_name')
              <p class="error">{{$message}}</p>
            @enderror
          </div>
        </div>
      <div class="col-md-6">
        <div class="form-group">
          <label class="label" for="name">Last Name<span class="required">*</span></label>
          <input type="text" class="form-control"  value="{{old('last_name')}}" name="last_name"  placeholder="Last Name" >
          @error('last_name')
            <p class="error">{{$message}}</p>
          @enderror
        </div>
      </div>
      <div class="col-md-6">
      <div class="form-group">
      <label class="label" for="email">Email Address<span class="required">*</span></label>
      <input type="email" class="form-control"  value="{{old('Email')}}" name="email" placeholder="Email" >
      @error('email')
            <p class="error">{{$message}}</p>
          @enderror
      </div>
      </div>
      <div class="col-md-6">
      <div class="form-group">
      <label class="label" for="subject">Mobile number<span class="required">*</span></label>
      <input type="text" class="form-control"  value="{{old('mobile_number')}}" name="mobile_number"  placeholder="Mobile number" >
      @error('mobile_number')
            <p class="error">{{$message}}</p>
          @enderror
      </div>
      </div>
    
      <div class="col-md-12">
       <div class="form-group">
      <label class="label" for="#">Message<span class="required">*</span></label>
      <textarea name="message" value="{{old('message')}}" class="form-control" id="message" cols="30" rows="4" placeholder="Message"></textarea>
      @error('message')
            <p class="error">{{$message}}</p>
          @enderror
      </div>
      </div>
        <div class="col-md-12">
        <div class="form-group text-center">
        <button type="submit"  class="btn btn-primary" fdprocessedid="guotk8">Send Message</button>
        <div class="submitting"></div>
        </div>
      </div>
      </div>
     </form>
    </div>
    </div>
    
    </div>
    </div>
</div>
</div>
</div>
    
@endsection





