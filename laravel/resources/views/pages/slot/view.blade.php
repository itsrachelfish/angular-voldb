<?php

use App\Helpers;

$url = "/slot/{$slot->id}/take";

$taken = false;
$self = false;
$other = false;

if(!empty($slot->user))
{
    $taken = true;
    if(Auth::check() && Auth::user()->id == $slot->user->id)
    {
        $self = true;
        $url = "/slot/{$slot->id}/release";
        //if the admin is trying to release themselves from a slot
        if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('department-lead'))
        {
            $url = "/slot/{$slot->id}/adminRelease";
        }
    }
    else
    {
        $other = true;
    }
    //if the admin is trying to release someone else from their slot
    if(Auth::check() && (Auth::user()->hasRole('admin') || Auth::user()->hasRole('department-lead')))
    {
        $adminUrl = "/slot/{$slot->id}/adminRelease";
    }
}
else
{
    if(Auth::check() && Auth::user()->hasRole('admin') || Auth::user()->hasRole('department-lead'))
    {
        $adminUrl = "/slot/{$slot->id}/adminAssign";
    }
}

?>

@extends('app')

@section('content')

    <h1>
        @if($taken)
            @if($self)
                Your Volunteer Shift for:
            @else
                Occupied Volunteer Shift for:
            @endif
        @else
            Available for Volunteering:
        @endif

        {{ $slot->department->name }} - {{ $slot->schedule->shift->name }}
    </h1>

    <hr>

    {!! Form::open(['url' => $url]) !!}
        <div>
            <label>Start Date</label>
            {{ $slot->start_date }}
        </div>

        <div>
            <label>Start Time</label>
            {{ $slot->start_time }}
        </div>

        <div>
            <label>End Time</label>
            {{ $slot->end_time }}
        </div>

        @if($slot->department->description)
            <label>About {{ $slot->department->name }}</label>
            <p>{!! nl2br(e($slot->department->description)) !!}</p>
        @endif

        @if($slot->schedule->shift->description)
            <label>About {{ $slot->schedule->shift->name }}</label>
            <p>{!! nl2br(e($slot->schedule->shift->description)) !!}</p>
        @endif

        @if($taken)
            <hr>

            @if($self)
                <p>
                    Are you sure you want to cancel volunteering for this shift?
                    By canceling, your slot will be available for other people to take.
                </p>

                <button type="submit" class="btn btn-danger">Release Shift</button>
            @else
                <p>
                    This slot has been taken by <b>{{ Helpers::displayName($slot->user) }}</b>.
                </p>

                @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('department-lead'))
                    <div class="profile">
                        <div class="row">
                            <div class="col-sm-2 title">Email</div>
                            <div class="col-sm-10 value">{{ $slot->user->email }}</div>
                        </div>

                        <div class="row">
                            <div class="col-sm-2 title">Full Name</div>
                            <div class="col-sm-10 value">{{ $slot->user->data->full_name or 'Not Provided' }}</div>
                        </div>

                        <div class="row">
                            <div class="col-sm-2 title">Burner Name</div>
                            <div class="col-sm-10 value">{{ Helpers::displayName($slot->user, 'Not Provided') }}</div>
                        </div>

                        <div class="row">
                            <div class="col-sm-2 title">Performance</div>
                            <div class="col-sm-10 value volunteer">
                                <input type="hidden" class="csrf-token" value="{{ csrf_token() }}">
                                <input type="hidden" class="slot-number" value="{{ $slot->id }}">

                                <select class="volunteer-status" data-status="{{ $slot->status }}">
                                    <option value="">Select One</option>
                                    <option value="flaked">Flaked</option>
                                    <option value="late">Late</option>
                                    <option value="ontime">On Time</option>
                                    <option value="excellent">Excellent</option>
                                </select>

                                <span class="buttons">&ensp;
                                    <a class="save-status">Save</a>&ensp;
                                    <a class="cancel-status">Cancel</a>
                                </span>
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        @else
            {{-- If nobody has taken this slot, display more information and the take button --}}

            @if($slot->schedule->password)
                <div>
                    @include('partials/form/text', ['name' => 'password', 'label' => 'This shift requires a password', 'help' => "This shift has been reserved. You must recieve a password from the department lead in order to take this shift."])
                </div>
            @else
                <div>
                    <label>Allowed User Groups</label>
                    <ul>
                        @foreach($slot->schedule->getRoles() as $scheduleRole)
                            <li>{{ ucwords($scheduleRole->role->name) }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <hr>

            <p>
                Are you sure you want to volunteer for this shift?
                By registering, you will be expected to perform the duties of this shift for the entire time listed.
                Please arrive at least 15 minutes ahead of time to be briefed by the previous shift team and answer any questions you have.
            </p>

            <button type="submit" class="btn btn-success">Take Shift</button>
        @endif

        <a href="/event/{{ $slot->event->id }}" class="btn btn-primary">Back to Event</a>

        @if((Auth::user()->hasRole('admin') || Auth::user()->hasRole('department-lead')) && $taken && $other)
        <p>
            Are you sure you want to remove this user for this shift?
            By releasing {{$slot->user->data->burner_name}}, their slot will be available for other people to take.
        </p>
        <button formaction="{{ $adminUrl }}" type="submit" class="btn btn-danger">Release Shift</button>
        @endif
        @if((Auth::user()->hasRole('admin') || Auth::user()->hasRole('department-lead')) && !$taken)
            <a class="btn btn-warning add-volunteer">Add Volunteer</a>
            <input type="hidden" class="csrf-token" name="_token" value="{{ csrf_token() }}">
            <div class="row user-search hidden">
                <div class="col-md-11">
                    @include('partials/form/text',
                    [
                        'name' => 'user-search',
                        'label' => 'Search for a user',
                        'placeholder' => 'rachel@apogaea.com',
                        'help' => 'You can search by user ID, username, or email'
                    ])
                </div>

                <div class="col-md-1 search">
                    <button class="user-search btn btn-success"><i class="glyphicon glyphicon-search"></i></button>
                </div>
            </div>

            <div class="user-wrap">
                <div class="loading hidden">
                    Loading user data...

                    <div class="spinner"></div>
                </div>

                <div class="users hidden">
                    <h3>Search results</h3>

                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Real Name</th>
                                <th>Burner Name</th>
                                <th>Email</th>
                                <th>Assign to Shift?</th>
                            </tr>

                            <tr class="template hidden">
                                <td><b>{user_id}</b></td>
                                <td><a href="/user/{user_id}">{username}</a></td>
                                <td>{full_name}</td>
                                <td>{burner_name}</td>
                                <td>{email}</td>
                                <td>
                                    <input type="radio" name="user" value="{user_id}">

                                </td>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td><b>1</b></td>
                                <td><a href="/user/1">username</a></td>
                                <td>Example User</td>
                                <td>example@user.com</td>
                                <td>softburnedbeanie<input type="hidden" name="user-name" value="softburnedbeanie"></td>
                                <td>
                                    <input type="radio" name="user-report[]" value="1">
                                </td>
                            </tr>

                            <tr>
                                <td><b>2</b></td>
                                <td><a href="/user/2">user2</a></td>
                                <td>Another User</td>
                                    <td>another@user.com</td>
                                <td>
                                    <input type="radio" name="user-report[]" value="2">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <button formaction="{{ $adminUrl }}" class="btn btn-primary" type="submit">Assign User</button>
                </div>
            </div>
        @endif

    {!! Form::close() !!}
@endsection
