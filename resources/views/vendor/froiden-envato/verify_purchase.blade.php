@php
    header("Location: " . route('login'), true, 302);
    exit();
@endphp
<script>
    window.location.href = "{{ route('login') }}";
</script>
