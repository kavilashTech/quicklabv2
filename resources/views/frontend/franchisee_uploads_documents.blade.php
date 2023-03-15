@extends('frontend.layouts.app')

@section('content')
    <section class="gry-bg py-5">
        <div class="profile">
            <div class="container">
                <div class="row">
                    <div class="col-xxl-4 col-xl-5 col-lg-6 col-md-8 mx-auto">
                        <div class="card">
                            <div class="text-center pt-4">
                                <h1 class="h4 fw-600">
                                    Franchisee uploads documents
                                </h1>
                            </div>

                            <div class="px-4 py-3 py-lg-4">
                                <div class="">
                                    <form class="form-default" role="form" action="{{ route('franchisee-document-upload') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <div class="form-group email-form-group">
                                            <label>Select ID Proof</label>
                                            <input type="file" class="form-control" placeholder="Select ID Proof" name="id_proof" id="id_proof" autocomplete="off">
                                        </div>

                                        <div class="form-group">
                                            <label>Select Address Proof</label>
                                            <input type="file" class="form-control" value="{{ old('email') }}" placeholder="Select address proof" name="address_proof" id="address_proof" autocomplete="off">
                                        </div>
                                        <div class="mb-5">
                                            <input type="hidden" name="email" value="{{$data['email']}}">
                                            <button type="submit" class="btn btn-primary btn-block fw-600">Upload</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('script')
    
@endsection
