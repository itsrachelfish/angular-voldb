<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

class Slot extends Model
{
    use SoftDeletes;

    protected $fillable = ['schedule_id', 'start_date', 'start_time', 'end_time', 'row', 'status'];

    // Slots belong to the schedule
    public function schedule()
    {
        return $this->belongsTo('App\Models\Schedule');
    }

    // Slots can also belong to a user
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    // Convenience for getting the department of a slot
    public function getDepartmentAttribute()
    {
        return $this->schedule->department;
    }

    // Convenience for getting the event of a slot
    public function getEventAttribute()
    {
        return $this->schedule->event;
    }

    // Helper function to get the number of seconds in a timestamp
    static public function timeToSeconds($time)
    {
        $parsed = date_parse_from_format('H:i', $time);
        $seconds = 0;

        $seconds += $parsed['hour'] * 60 * 60;
        $seconds += $parsed['minute'] * 60;

        return $seconds;
    }

    // Helper function to convert seconds back into hours
    static public function secondsToTime($seconds)
    {
        // Set timezone to UTC, otherwise PHP will convert the timestamp to the current timezone
        date_default_timezone_set('UTC');
        return date('H:i', $seconds);
    }

    // Helper function to generate slots based on schedule information
    static public function generate($schedule, $existingRows = null)
    {
        if(is_null($schedule->start_date))
        {
            self::generateWithoutDate($schedule, $existingRows);
        }
        else
        {
            self::generateWithDate($schedule, $existingRows);
        }
    }

    // Generate slots based on the day and time of the schedule
    static public function generateWithDate($schedule, $existingRows = null)
    {
        // Do we want to keep any existing rows?
        if($existingRows)
        {
            $row = $existingRows + 1;
        }
        else
        {
            // Delete all existing slots for this shift
            Slot::where('schedule_id', $schedule->id)->whereNull('user_id')->forceDelete();
            Slot::where('schedule_id', $schedule->id)->delete();
            $row = 1;
        }

        // Set up required variables
        $timezone = config('app.timezone');
        $end_date = new Carbon($schedule->end_date, $timezone);
        $dates = json_decode($schedule->getAttribute('dates'));
        $volunteers = (int)$schedule->volunteers;

        // Generate slots for each requested volunteer
        while($row <= $volunteers)
        {
            $date = new Carbon($schedule->start_date, $timezone);

            // Loop over all days between the start and end date
            while($date->lte($end_date))
            {
                // Only create slots if the current date exists in the list of selected dates
                if(in_array($date->format('Y-m-d'), $dates))
                {
                    // Convert shift times to seconds
                    $start = Slot::timeToSeconds($schedule->start_time);
                    $end = Slot::timeToSeconds($schedule->end_time);
                    $duration = Slot::timeToSeconds($schedule->duration);

                    // Now loop over the times based on the slot duration
                    for($time = $start; $time + $duration <= $end; $time += $duration)
                    {
                        $slot =
                        [
                            'schedule_id' => $schedule->id,
                            'start_date' => $date->format('Y-m-d'),
                            'start_time' => Slot::secondsToTime($time),
                            'end_time' => Slot::secondsToTime($time + $duration),
                            'row' => $row
                        ];

                        Slot::create($slot);
                    }
                }

                // All done? Move onto the next day
                $date->addDay();
            }

            $row++;
        }
    }

    // Generate slots based on the number of volunteers needed
    static public function generateWithoutDate($schedule, $existingRows = null)
    {
        // Do we want to keep any existing rows?
        if($existingRows)
        {
            $row = $existingRows + 1;
        }
        else
        {
            // Delete all existing slots for this shift
            Slot::where('schedule_id', $schedule->id)->whereNull('user_id')->delete();
            $row = 1;
        }

        // Set up required variables
        $volunteers = (int)$schedule->volunteers;

        // Generate slots for each requested volunteer
        while($row <= $volunteers)
        {
            $slot =
            [
                'schedule_id' => $schedule->id,
                'row' => $row
            ];

            Slot::create($slot);
            $row++;
        }
    }

    static public function volunteersChanged($schedule, $oldAmount)
    {
        $newAmount = $schedule->volunteers;
        $difference = $newAmount - $oldAmount;

        // If new rows are being added
        if($difference > 0)
        {
            // Generate new slots passing in the old number of volunteers as a starting point
            Slot::generate($schedule, $oldAmount);
        }
        // If old rows are being removed
        else
        {
            // Delete all slots where the row is greater than the number of volunteers requested
            Slot::where('schedule_id', $schedule->id)->where('row', '>', $schedule->volunteers)->whereNull('user_id')->forceDelete();
            Slot::where('schedule_id', $schedule->id)->where('row', '>', $schedule->volunteers)->delete();
        }
    }
}
