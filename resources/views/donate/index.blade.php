@extends('layouts.master')

@section('css')
<link href="{{ URL::asset('assets/libs/chartist/chartist.min.css')}}" rel="stylesheet" type="text/css" />
@include('layouts.datatable')
@endsection

@section('content')
<div class="row align-items-center">
    <div class="col-sm-6">
        <div class="page-title-box">
            <h4 class="font-size-18">Derma</h4>
            <!-- <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item active">Welcome to Veltrix Dashboard</li>
            </ol> -->
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            {{-- <div class="card-header">List Of Applications</div> --}}
            <div>
                {{-- route('sekolah.create')  --}}
                <a style="margin: 19px; float: right;" href="{{ route('donate.create') }}" class="btn btn-primary"> <i
                        class="fas fa-plus"></i> Tambah Derma</a>
            </div>

            <div class="card-body">

                @if(count($errors) > 0)
                <div class="alert alert-danger">
                    <ul>
                        @foreach($errors->all() as $error)
                        <li>{{$error}}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                @if(\Session::has('success'))
                <div class="alert alert-success">
                    <p>{{ \Session::get('success') }}</p>
                </div>
                @endif

                {{-- <div align="right">
                            <a href="{{route('admin.create')}}" class="btn btn-primary">Add</a>
                <br />
                <br />
            </div> --}}
            <div class="table-responsive">
                <table id="donationTable" class="table table-bordered table-striped">
                    <thead>
                        <tr style="text-align:center">
                            <th> Nama Derma </th>
                            <th> Penerangan </th>
                            <th> Harga (RM) </th>
                            <th> Status </th>
                            <th> Action </th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($donate as $row)
                        <tr>
                            <td>{{$row['nama']}}</td>
                            <td>{{$row['description']}}</td>
                            <td> {{ number_format($row['amount'], 2) ?? '0' }} </td>
                            @if($row['status'] =='1')
                            <td style="text-align: center">
                                <p class="btn btn-success m-1"> Aktif </p>
                            </td>
                            @else
                            <td style="text-align: center">
                                <p class="btn btn-danger m-1"> Tidak Aktif </p>
                            </td>
                            @endif
                            <td>
                                <div class="d-flex justify-content-center">
                                    {{-- <a href="{{ route('school.edit', $row['id']) }}" class="btn btn-primary
                                    m-1">Edit</a> --}}
                                    <a href="{{ route('donate.edit', $row['id']) }}"
                                        class="btn btn-primary m-1">Edit</a>

                                    <button class="btn btn-danger m-1"
                                        onclick="return confirm('Adakah anda pasti ?')">Buang</button>
                                </div>
                            </td>

                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</div>

@endsection


@section('script')
<!-- Peity chart-->
<script src="{{ URL::asset('assets/libs/peity/peity.min.js')}}"></script>

<!-- Plugin Js-->
<script src="{{ URL::asset('assets/libs/chartist/chartist.min.js')}}"></script>

<script src="{{ URL::asset('assets/js/pages/dashboard.init.js')}}"></script>

<script>
    $(document).ready(function () {
        $('#donationTable').DataTable({
            ordering: true
        });
    });
</script>
@endsection