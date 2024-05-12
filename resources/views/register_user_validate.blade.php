@extends('inc.layout')

@section('content')
    @if ($is_desktop)
        <div class="w-full h-screen bg-red-50 flex justify-center items-center">
            <div class="text-red-900">
                <div class="font-bold -mt-20" id="loading">Sorry use a mobile device !</div>
            </div>
        </div>
    @elseif ($is_Invalid)
        <div class="w-full h-screen bg-red-50 flex justify-center items-center">
            <div class="text-red-900">
                <div class="font-bold -mt-20" id="loading">Sorry request is invalid !</div>
            </div>
        </div>
    @elseif ($is_expired)
        <div class="w-full h-screen bg-red-50 flex justify-center items-center">
            <div class="text-red-900">
                <div class="font-bold -mt-20" id="loading">Sorry auth link is expired !</div>
            </div>
        </div>
    @else
        <div class="w-full h-screen bg-yellow-50 flex justify-center items-center" id="pin-wrapper">
            <div class="-mt-20">
                <div class="font-bold text-yellow-900 block" id="pin-loading">Please wait ...</div>
                <div class="font-bold text-red-900 hidden" id="pin-error"></div>
                <div id="pin" class="hidden">
                    <div class="text-xs mb-2 underline underline-offset-4">Auth pin</div>
                    <div class="font-bold text-4xl text-yellow-900 tracking-wider" id="pin-value"></div>
                </div>
            </div>
        </div>

        {{-- <div>
            {{ $browser }}<br>
            {{ $platform }}<br>
            {{ $device }}<br>
        </div> --}}
    @endif

    <script>
        $(document).ready(() => {

            function getPin(visitor_id) {
                $.ajax({
                    url: "/register-user/retrieve-pin",
                    type: "POST",
                    data: {
                        visitor_id: "visitor_id"
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: (res) => {
                        $("#pin-loading").removeClass("block");
                        $("#pin-loading").addClass("hidden");
                        $("#pin").removeClass("hidden");
                        $("#pin").addClass("block");
                        $("#pin-value").html(res.pin);
                        console.log(res);
                    },
                    error: (xhr, _, err) => {
                        $("#pin-wrapper").removeClass("bg-yellow-50");
                        $("#pin-wrapper").addClass("bg-red-50");
                        $("#pin-loading").removeClass("block");
                        $("#pin-loading").addClass("hidden");
                        $("#pin-error").removeClass("hidden");
                        $("#pin-error").addClass("block");
                        $("#pin-error").html(xhr.responseJSON);
                        console.log("Error:", xhr.responseJSON);
                    }
                })
            }
            // setTimeout(getPin, 100);

            const fpPromise = import('https://fpjscdn.net/v3/v57kXaAAlgXbSMi9ILyH')
                .then(FingerprintJS => FingerprintJS.load({
                    region: "eu"
                }))

            if ({{!$is_Invalid && !$is_desktop && !$is_expired}}) {

                fpPromise
                    .then(fp => fp.get())
                    .then(result => {
                        const visitorId = result.visitorId
                        console.log(visitorId)
                        visitorId ? getPin(result.visitorId) : alert("Error disble ad blockers")
                    })
            }

        });
    </script>
@endsection
