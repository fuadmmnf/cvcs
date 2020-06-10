@extends('adminlte::page')

@section('title', 'CVCS | পদবি সমূহ')

@section('css')
  <style type="text/css">
    .membersfrow {
      
    }
  </style>
@stop

@section('content_header')
    <h1>
      পদবি সমূহ
      <div class="pull-right">
        @if(Auth::user()->role == 'admin')
        <a class="btn btn-success" href="#!" title="পদবি যোগ করুন (কাজ চলছে...)"><i class="fa fa-fw fa-plus" aria-hidden="true"></i></a> {{-- {{ route('dashboard.createbulkpayer') }} --}}
        @endif
      </div>
    </h1>
@stop

@section('content')
  @if(Auth::user()->role == 'admin')
  <div class="table-responsive">
    <table class="table table-bordered">
      <thead>
        <tr>
          <th width="5%">#</th>
          <th>নাম</th>
          <th>সদস্য সংখ্যা</th>
          <th width="10%">Action</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td style="background: #EEEEEE !important;">{{ bangla(1) }}</td>
          <td style="background: #EEEEEE !important;"><a href="{{ route('dashboard.designation.members', $memberpos->id) }}">{{ $memberpos->name }}</a></td>
          <td style="background: #EEEEEE !important;">
            {{-- {{ bangla($memberpos->users->where('activation_status', 1)->count()) }} জন --}}
            @php
              $totalmembers = 0;
              foreach ($memberpos->users as $member) {
                if($member->activation_status == 1) {
                  $totalmembers++;
                }
              }
            @endphp
            {{ bangla($totalmembers) }} জন
          </td>
          <td style="background: #EEEEEE !important;">
            <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#editModal{{ $memberpos->id }}" data-backdrop="static" title="পদবি সম্পাদনা করুন"><i class="fa fa-pencil"></i></button>
            <a href="{{ route('dashboard.designation.members', $memberpos->id) }}" class="btn btn-sm btn-success" title="পদবির সদস্য দেখুন">
              <i class="fa fa-eye"></i>
            </a>
            <!-- Edit Modal -->
            <!-- Edit Modal -->
            <div class="modal fade" id="editModal{{ $memberpos->id }}" role="dialog">
              <div class="modal-dialog modal-md">
                <div class="modal-content">
                  <div class="modal-header modal-header-primary">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">পদবি সম্পাদনা</h4>
                  </div>
                  {!! Form::model($memberpos, ['route' => ['dashboard.updatebranch', $memberpos->id], 'method' => 'PUT', 'class' => 'form-default']) !!}
                        
                  <div class="modal-body">
                    কাজ চলছে...
                  </div>
                  <div class="modal-footer">
                    {!! Form::submit('দাখিল করুন', array('class' => 'btn btn-primary')) !!}
                    <button type="button" class="btn btn-default" data-dismiss="modal">ফিরে যান</button>
                  </div>
                  {!! Form::close() !!}
                </div>
              </div>
            </div>
            <!-- Edit Modal -->
            <!-- Edit Modal -->
            <a href="{{ url('dashboard/reports/export/designation/members/list/pdf?position_id=' . $memberpos->id) }}" class="btn btn-sm btn-info" title="সদস্য তালিকা ডাউনলোড করুন">
              <i class="fa fa-download"></i>
            </a>
          </td>
        </tr>
        @foreach($positions as $position)
        <tr>
          <td>{{ bangla($position->id + 1) }}</td>
          <td><a href="{{ route('dashboard.designation.members', $position->id) }}">{{ $position->name }}</a></td>
          <td>
            {{ bangla($position->users->where('activation_status', 1)->count()) }} জন
            @php
              $totalmembers = 0;
              foreach ($position->users as $member) {
                if($member->activation_status == 1) {
                  $totalmembers++;
                }
              }
            @endphp
            {{ bangla($totalmembers) }} জন
          </td>
          <td>
            <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#editModal{{ $position->id }}" data-backdrop="static" title="পদবি সম্পাদনা করুন"><i class="fa fa-pencil"></i></button>
            <a href="{{ route('dashboard.designation.members', $position->id) }}" class="btn btn-sm btn-success" title="পদবির সদস্য দেখুন">
              <i class="fa fa-eye"></i>
            </a>
            <!-- Edit Modal -->
            <!-- Edit Modal -->
            <div class="modal fade" id="editModal{{ $position->id }}" role="dialog">
              <div class="modal-dialog modal-md">
                <div class="modal-content">
                  <div class="modal-header modal-header-primary">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">পদবি সম্পাদনা</h4>
                  </div>
                  {!! Form::model($position, ['route' => ['dashboard.updatebranch', $position->id], 'method' => 'PUT', 'class' => 'form-default']) !!}
                        
                  <div class="modal-body">
                    কাজ চলছে...
                  </div>
                  <div class="modal-footer">
                    {!! Form::submit('দাখিল করুন', array('class' => 'btn btn-primary')) !!}
                    <button type="button" class="btn btn-default" data-dismiss="modal">ফিরে যান</button>
                  </div>
                  {!! Form::close() !!}
                </div>
              </div>
            </div>
            <!-- Edit Modal -->
            <!-- Edit Modal -->
            <a href="{{ url('dashboard/reports/export/designation/members/list/pdf?position_id=' . $position->id) }}" class="btn btn-sm btn-info" title="সদস্য তালিকা ডাউনলোড করুন">
              <i class="fa fa-download"></i>
            </a>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  {{ $positions->links() }}

  @else
    <span class="text-red"><i class="fa fa-exclamation-triangle"></i> দুঃখিত, আপনার এই পাতাটি দেখবার অনুমতি নেই!</span>
  @endif
@stop

@section('js')

@stop