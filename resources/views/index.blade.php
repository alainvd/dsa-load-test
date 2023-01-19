DSA Load Test

@if(Session::has('status'))
    <p class="alert">{{ Session::get('status') }}</p>
@endif

<form action="{{route('fire')}}" method="post">
    <input type="number" name="limit">
    @csrf
    <button type="submit">Fire</button>
</form>
