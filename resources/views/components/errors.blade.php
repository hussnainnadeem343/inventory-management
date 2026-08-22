@if($errors->any())<div class="alert alert-danger"><strong>Please correct the following:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{$error}}</li>@endforeach</ul></div>@endif
