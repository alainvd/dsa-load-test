DSA Load Test - Single Statement

@if(Session::has('status'))
    <p class="alert">{{ Session::get('status') }}</p>
@endif

<div>
    <a href="{{ route('single') }}">Single Statement</a> |
    <a href="{{ url('/') }}">Batch Statements</a>
</div>

<form action="{{ route('fire-single') }}" method="post">
    <p>Number of statements to send (1000 by 1000):</p>
    <input type="number" name="limit">
    @csrf
    <button type="submit">Fire Single</button>
</form>
