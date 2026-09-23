<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * English strings for Video Observation.
 *
 * @package mod_videoobserve
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['addevent'] = 'Add event';
$string['aggregatereport'] = 'Aggregate observation report';
$string['allowcomments'] = 'Allow a textual note for each observation';
$string['allowintervals'] = 'Allow interval observations';
$string['averageperstudent'] = 'Average marks per student';
$string['backtoactivity'] = 'Back to activity';
$string['completiondetail:occurrences'] = 'Register at least {$a} observation(s)';
$string['completiondetail:percent'] = 'Watch at least {$a}% of the video';
$string['completionoccurrences'] = 'Require number of observations';
$string['completionoccurrences_help'] = 'The activity can be completed only after this many observations have been registered. Enter 0 to disable this rule.';
$string['completionpercent'] = 'Require watched percentage';
$string['completionpercent_help'] = 'The activity can be completed only after this percentage of unique video content has been watched. Enter 0 to disable this rule.';
$string['completionrules'] = '';
$string['deleteeventconfirm'] = 'Delete this observation event?';
$string['deleteoccurrenceconfirm'] = 'Delete this observation?';
$string['deletereferenceconfirm'] = 'Delete this reference observation?';
$string['editevent'] = 'Edit event';
$string['errormaxfiles'] = 'Only one file can be uploaded.';
$string['event'] = 'Event';
$string['eventdeleted'] = 'Observation event deleted.';
$string['eventdescription'] = 'Description or observation criterion';
$string['eventfrequency'] = 'Events most identified';
$string['eventinuse'] = 'This event already has student observations and cannot be deleted. Edit it instead.';
$string['eventname'] = 'Event name';
$string['eventrequired'] = 'Required event';
$string['eventsaved'] = 'Observation event saved.';
$string['exportcsv'] = 'Export CSV';
$string['finalsheet'] = 'Final observation sheet';
$string['finishinterval'] = 'Finish interval';
$string['identifiedmoments'] = 'Identified moments';
$string['intervalstarted'] = 'Interval started.';
$string['invalidtolerance'] = 'The tolerance must be from 0 to 300 seconds.';
$string['invalidurl'] = 'Enter a valid direct video URL.';
$string['invalidvimeo'] = 'Enter a valid Vimeo video ID or Vimeo URL.';
$string['invalidyoutube'] = 'Enter a valid YouTube video ID or YouTube URL.';
$string['manageobservationsheet'] = 'Manage observation sheet';
$string['manageobservationsheet_help'] = 'Create the event types learners should look for. The same event can be marked any number of times during the video.';
$string['managereference'] = 'Reference observations';
$string['markings'] = 'Markings';
$string['marknow'] = 'Mark this moment';
$string['meandeviation'] = 'Mean time deviation';
$string['modulename'] = 'Video Observation';
$string['modulename_help'] = 'A systematic video observation activity where learners mark recurring events, moments or intervals and add notes.';
$string['modulenameplural'] = 'Video observations';
$string['mostidentifiedmoments'] = 'Moments most identified';
$string['noactivities'] = 'There are no visible Video Observation activities in this course.';
$string['noevents'] = 'No observation events have been created yet.';
$string['nomarkingsyet'] = 'No observations have been registered yet.';
$string['noreference'] = 'No teacher reference observations were registered.';
$string['nostudents'] = 'No students were found for the current group or activity.';
$string['notavailable'] = 'N/A';
$string['observationnote'] = 'Observation note';
$string['observations'] = 'Observations';
$string['observationsettings'] = 'Observation settings';
$string['observationsheet'] = 'Observation sheet';
$string['observationtimeline'] = 'Observation timeline';
$string['player'] = 'Video player';
$string['pluginadministration'] = 'Video Observation administration';
$string['pluginname'] = 'Video Observation';
$string['poster'] = 'Poster image';
$string['privacy:exportpath'] = 'Video Observation data';
$string['privacy:metadata:occurrences'] = 'Stores the events and moments observed by a learner.';
$string['privacy:metadata:occurrences:endtime'] = 'The optional end time of an interval observation.';
$string['privacy:metadata:occurrences:eventtypeid'] = 'The observation-sheet event that was marked.';
$string['privacy:metadata:occurrences:note'] = 'The learner textual note attached to the occurrence.';
$string['privacy:metadata:occurrences:starttime'] = 'The start time of the observed occurrence.';
$string['privacy:metadata:occurrences:timecreated'] = 'When the observation was created.';
$string['privacy:metadata:occurrences:timemodified'] = 'When the observation was last modified.';
$string['privacy:metadata:occurrences:userid'] = 'The user who registered the observation.';
$string['privacy:metadata:progress'] = 'Stores consolidated video viewing progress for a learner.';
$string['privacy:metadata:progress:duration'] = 'The known video duration.';
$string['privacy:metadata:progress:lastposition'] = 'The last saved playback position.';
$string['privacy:metadata:progress:percent'] = 'The watched percentage derived from unique watched content.';
$string['privacy:metadata:progress:segments'] = 'The consolidated video segments actually watched.';
$string['privacy:metadata:progress:timemodified'] = 'When progress was last updated.';
$string['privacy:metadata:progress:uniquewatched'] = 'The number of unique seconds watched.';
$string['privacy:metadata:progress:userid'] = 'The user whose progress is stored.';
$string['privacy:metadata:references'] = 'Stores teacher-created reference observations.';
$string['privacy:metadata:references:createdby'] = 'The user who created the reference observation.';
$string['privacy:metadata:references:endtime'] = 'The optional expected event end time.';
$string['privacy:metadata:references:eventtypeid'] = 'The observation event represented by the reference mark.';
$string['privacy:metadata:references:note'] = 'The note attached to the reference mark.';
$string['privacy:metadata:references:starttime'] = 'The expected event start time.';
$string['privacy:metadata:references:timecreated'] = 'When the reference mark was created.';
$string['quantity'] = 'Quantity';
$string['referenceanswer'] = 'Teacher reference answer';
$string['referencecount'] = 'Reference marks';
$string['referenceextras'] = 'Additional marks';
$string['referenceinstructions'] = 'Watch the same video and mark the expected events. These marks are used only for comparison and can be hidden from students.';
$string['referencematches'] = 'Reference matches';
$string['referencemissed'] = 'Reference marks missed';
$string['referencetolerance'] = 'Reference tolerance (seconds)';
$string['referencetolerance_help'] = 'Maximum difference, in seconds, between a student mark and the closest teacher reference mark for them to be considered a reference match.';
$string['referencevisible'] = 'The teacher reference answer is visible for this activity.';
$string['requiredsourceurl'] = 'Enter the URL or video ID for the selected source.';
$string['requiredvideofile'] = 'A video file is required when the Moodle upload source is selected.';
$string['resumeplayback'] = 'Resume playback from the last saved position';
$string['saveerror'] = 'The observation could not be saved.';
$string['saveevent'] = 'Save event';
$string['secondsvalue'] = '{$a} s';
$string['showreference'] = 'Show the teacher reference answer to students';
$string['sortorder'] = 'Sort order';
$string['sourceupload'] = 'Video uploaded to Moodle';
$string['sourceurl'] = 'Direct video URL';
$string['sourcevimeo'] = 'Vimeo';
$string['sourceyoutube'] = 'YouTube';
$string['startinterval'] = 'Start interval';
$string['student'] = 'Student';
$string['studentdifferences'] = 'Differences between students';
$string['students'] = 'Students';
$string['studentsidentified'] = 'Students who identified it';
$string['timewindow'] = 'Time window';
$string['totalmarkings'] = 'Total marks';
$string['trackingerror'] = 'Video progress could not be synchronised now.';
$string['videofile'] = 'Video file';
$string['videoheader'] = 'Video';
$string['videoobserve:addinstance'] = 'Add a new Video Observation activity';
$string['videoobserve:exportreport'] = 'Export Video Observation reports';
$string['videoobserve:manageevents'] = 'Manage the observation sheet';
$string['videoobserve:managereference'] = 'Manage teacher reference observations';
$string['videoobserve:view'] = 'View and use Video Observation';
$string['videoobserve:viewreport'] = 'View aggregate Video Observation reports';
$string['videoobservename'] = 'Activity name';
$string['videosource'] = 'Video source';
$string['videourl'] = 'Video URL or video ID';
$string['watchedpercent'] = 'Watched';
