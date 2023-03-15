@extends('frontend.layouts.user_panel')
<style>
    .error{
        color:red;
        padding-top: 10px;
    }
</style>
@section('panel_content')
<div class="aiz-titlebar mt-2 mb-4">
            <div class="container">
                <div class="row align-items-center">
                        <div class="card" style="width: 100%;">
                            <div class="text-center pt-4">
                                <h2 class="h4 fw-600">
                                    {{ translate('Franchisee Registration')}}
                                </h2>
                                @if(Auth::user()->status == 3)
                                    <h1 class="h4 fw-600" style="color:red;">
                                        Your registration request sent successfully please wait for admin Approval
                                    </h1>
                                @endif
                            </div>
                            <div class="px-4 py-3 py-lg-4">
                                <div class="">
                                    <form id="franchisee-documents-form" name ="franchisee-documents-form" class="form-default" role="form" action="{{ route('franchisee-registration-request') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <div class="form-group">
                                            <label class="col-md-2 col-form-label">{{ translate('Full Name') }}</label>
                                            <input type="text" class="form-control" value="{{ Auth::user()->name }}" placeholder="{{  translate('Full Name') }}" name="name" readonly>
                                        </div>
                                       
                                        <div class="form-group">
                                            <label class="col-md-2 col-form-label">{{ translate('Email') }}</label>
                                            <input type="email" class="form-control" value="{{ Auth::user()->email }}" placeholder="{{  translate('Email') }}" name="email" readonly>
                                        </div>

                                        <div class="form-group">
                                            <label class="col-md-2 col-form-label">{{ translate('Mobile Number') }}</label>
                                            <input type="tel" class="form-control{{ $errors->has('phone') ? ' is-invalid' : '' }}" value="{{ Auth::user()->phone }}" placeholder="{{  translate('Mobile Number (No prefix)') }}" name="phone" required readonly>
                                        </div>

                                        <h6>Preferred Location</h6>
                                        <div class="form-group">
                                            <label class="col-md-2 col-form-label">{{ translate('State') }}</label>
                                            <select class="form-control aiz-selectpicker" data-live-search="true" name="state_id" disabled>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label class="col-md-2 col-form-label">{{ translate('City') }}</label>
                                            <select class="form-control mb-3 aiz-selectpicker" data-live-search="true" name="city_id" disabled>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label class="col-md-3 col-form-label">{{ translate('Total Experience') }} (Years)</label>
                                            <input type="text" class="form-control" value="{{ Auth::user()->total_experience }}" placeholder="{{  translate('Total Experience') }}" name="total_experience" id="total_experience" @if(!empty(Auth::user()->total_experience)) readonly @endif>
                                            <span class="expError" style="color:red;" role="alert">
                                        </div>

                                        <div class="form-group">
                                            <label for="franchisee_id_proof">Upload Address Id (.jpg / .png / .pdf filetypes only allowed.max file size: 1 MB)</label>
                                            <input type="file" class="form-control-file" id="franchisee_id_proof" accept=".jpg, .png, .pdf" name="franchisee_id_proof" @if (!empty(Auth::user()->franchisee_id_proof)) disabled @endif>
                                            <span class="idProofError" style="color:red;" role="alert"></span> <br>
                                            @php
                                                $idProofArray[2] = "";
                                                if(!empty(Auth::user()->franchisee_id_proof)){
                                                    $idProof = Auth::user()->franchisee_id_proof;
                                                    $idProofArray = explode('_',$idProof);
                                                }
                                            @endphp
                                            @if(!empty(Auth::user()->franchisee_id_proof))
                                                <span><strong>{{ $idProofArray[2] }}</strong></span>
                                            @endif
                                        </div>
                                        <div class="form-group">
                                            <label for="franchisee_pan_card">Upload PAN (.jpg / .png / .pdf filetypes only allowed.max file size: 1 MB)</label>
                                            <input type="file" class="form-control-file" id="franchisee_pan_card" accept=".jpg, .png, .pdf" name="franchisee_pan_card" @if (!empty(Auth::user()->franchisee_pan_card)) disabled @endif>
                                            <span class="panCardError" style="color:red;" role="alert"></span> <br>
                                            @php
                                                $pancardArray[2] = "";
                                                if(!empty(Auth::user()->franchisee_pan_card)){
                                                    $pancard = Auth::user()->franchisee_pan_card;
                                                    $pancardArray = explode('_',$pancard);
                                                }
                                            @endphp
                                            @if(!empty(Auth::user()->franchisee_pan_card))
                                                <span><strong>{{ $pancardArray[2] }}</strong></span>
                                            @endif
                                        </div>

                                        <div class="form-check">
                                            @if(!empty(Auth::user()->franchisee_terms_check))
                                                <input class="form-check-input" type="checkbox" value="1" name="franchisee_terms_check" id="franchisee_terms_check" checked disabled>
                                            @else
                                                <input class="form-check-input" type="checkbox" value="" name="franchisee_terms_check" id="franchisee_terms_check">
                                            @endif
                                            <label class="form-check-label" for="franchisee_terms_check">
                                              I accept to the terms and conditions of Quicklab.
                                            </label>
                                            <span class="termError" style="color:red;" role="alert">
                                        </div>
                                        <div class="">
                                            *Franchise will be provided only for the experienced candidate and On exclusive basis for the selected location.<br>
                                            *Franchise income is dependent on sales obtained in their selected location. Separate login portals will be provided to the franchise to check their customers and earnings by their customers.<br>
                                            *Each product has some percentage of franchise commission like 5%-30% according to its category. which will be available by selecting the products in the franchise login portal.<br>
                                            *Sales will be obtained by digital marketing from the company side and also Franchise should work physically to visit customer places to promote our website and convince the customer to place an order. Even The Franchise can work in private institutes, colleges and Government organizations to get order. A formal quotation can be downloaded by selecting the products and entering customers details. All the support available like certificates and documents needed to participate in tenders.<br>
                                            *Need to make a Security deposit of 1000$ (Rs 80000) once the franchise is selected. Payment link and digital agreement will be sent to the franchise once they are confirmed.<br>
                                            *Franchise lock in period will be for three years. The deposit doesn't carry any interest.<br>
                                          </div> <br>
                                        @if(Auth::user()->status == 0)
                                            <div class="mb-5">
                                                <button type="button" id="submitBtn" class="btn btn-primary btn-block fw-600" onclick="return validateForm();">{{  translate('Submit') }}</button>
                                            </div>
                                        @endif
                                    </form>

                                </div>

                            </div>
                        </div>

                </div>
            </div>
</div>

@endsection

@section('script')

    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.min.js"></script>

    <script type="text/javascript">
        // Id proof validation
        $('#franchisee_id_proof').bind('change', function() {
            $(".idProofError").html("");
            $(".panCardError").html("");
            $('#submitBtn').prop("disabled", true);
            var a=0;
            var ext = $('#franchisee_id_proof').val().split('.').pop().toLowerCase();
            var picsize = (this.files[0].size);
            if ($.inArray(ext, ['png','jpg','pdf']) == -1){
                $(".idProofError").html("Invalid File Format! File Format Must Be JPG, PDF, PNG");
                a=0;
            }else if(picsize > 1000000){
                $(".idProofError").html("Maximum File Size Limit is 1MB.");
                a=0;
            }else{
                a=1;
                $('#submitBtn').prop("disabled", false);
            }
        });

        // Pan card validation
        $('#franchisee_pan_card').bind('change', function() {
            $(".idProofError").html("");
            $(".panCardError").html("");
            $('#submitBtn').prop("disabled", true);
            var a=0;
            var ext = $('#franchisee_pan_card').val().split('.').pop().toLowerCase();
            var picsize = (this.files[0].size);
            if ($.inArray(ext, ['png','jpg','pdf']) == -1){
                $(".panCardError").html("Invalid File Format! File Format Must Be JPG, PDF, PNG");
                a=0;
            }else if(picsize > 1000000){
                $(".panCardError").html("Maximum File Size Limit is 1MB.");
                a=0;
            }else{
                a=1;
                $('#submitBtn').prop("disabled", false);
            }
        });

        function validateForm(){
            var idProof = $("#franchisee_id_proof").val();
            var panCard = $("#franchisee_pan_card").val();
            var totalExp = $("#total_experience").val();
            var terms = $('#franchisee_terms_check').is(":checked");

            if(terms == true){
                $("#franchisee_terms_check").val(1);
            }

            $(".expError").html("");
            $(".idProofError").html("");
            $(".panCardError").html("");
            $(".termError").html("");

            if(totalExp == ""){
                $(".expError").html("Please enter total experience");
                return false;
            }else if(totalExp == 0){
                $(".expError").html("Please enter valid total experience");
                return false;
            }else if(idProof == ""){
                $(".idProofError").html("Please upload id proof");
                return false;
            }else if(panCard == ""){
                $(".panCardError").html("Please upload pan card");
                return false;
            }else if(terms == false){
                $(".termError").html("Please accept the terms and condition");
                return false;
            }
            else{
                $("#franchisee-documents-form").submit();
            }
        }

        $(document).ready(function(){
            $('.aiz-side-nav-link').each(function() { // find unique names
                $(this).addClass('disabled');
            });

            var authUserStateId = '<?= Auth::user()->state ?>';
            get_states('101')
            get_city(authUserStateId);
        });
        

        function get_states(country_id) {
            $('[name="state"]').html("");
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: "{{route('get-state-for-franchisee')}}",
                type: 'POST',
                data: {
                    country_id  : country_id
                },
                success: function (response) {
                    console.log(response); 
                    var obj = JSON.parse(response);
                    if(obj != '') {
                        $('[name="state_id"]').html(obj);
                        AIZ.plugins.bootstrapSelect('refresh');
                    }
                }
            });
        }

        function get_city(state_id) {
            $('[name="city"]').html("");
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: "{{route('get-city-for-franchisee')}}",
                type: 'POST',
                data: {
                    state_id: state_id
                },
                success: function (response) {
                    var obj = JSON.parse(response);
                    if(obj != '') {
                        $('[name="city_id"]').html(obj);
                        AIZ.plugins.bootstrapSelect('refresh');
                    }
                }
            });
        }
       
    </script>
@endsection
