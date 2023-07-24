<div id="{{$id}}" class="r-warning-container">
    <div class="r-warning-wrapper">
        <div class="r-warning-title">
            {{$title}}

            <span class="r-warning-btn-close">
                X
            </span>
        </div>

        <div class="r-warning-body">
            {{$message}}
        </div>
    </div>
</div>

{{--
<style>
    .r-warning-container {
        position: absolute;
        top: 200px;
        left: 50%;
        transform: translateX(-50%);
        background-color: #f8d7da;
        color: #721c24;
        padding: 2rem 1rem;
        border: 1px solid #f5c6cb;
        border-radius: .25rem;
    }

    .r-warning-wrapper {
        text-align: center;
    }

    .r-warning-title {
        text-align: center;
        font-size: 1.25rem;
        font-weight: 700;
    }

    .r-warning-body {
        font-size: 1rem;
        font-weight: 400;
    }

    .r-warning-btn-close {
        position: absolute;
        top: 3px;
        right: 3px;
        padding: 3px;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 700;
        background: rgba(0,0,0, 0.3);
        border-radius: 3px;
    }

</style>
--}}
