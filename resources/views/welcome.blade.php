DSA Load Test - Batch Statements

@if(Session::has('status'))
    <p class="alert">{{ Session::get('status') }}</p>
@endif

<div>
    <a href="{{ url('/') }}">Batch Statements</a> | 
    <a href="{{ route('single') }}">Single Statement</a>
</div>

<form action="{{route('fire')}}" method="post">
    <p>Number of batches to send (100 statements per batch):</p>
    <input type="number" name="limit">
    @csrf
    <button type="submit">Fire</button>
</form>
