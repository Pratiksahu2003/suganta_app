<p>Hi {{ $application->applicant_name ?? 'Applicant' }},</p>
<p>Your interview for <strong>{{ $application->job->title ?? 'the position' }}</strong> at <strong>{{ $application->job->company_name ?? 'the company' }}</strong> has been scheduled.</p>
<p><strong>Date & Time:</strong> {{ optional($application->interview_date)->format('M d, Y g:i A') ?? 'TBA' }}<br>
<strong>Location:</strong> {{ $application->interview_location ?? 'TBA' }}</p>
<p>{{ $application->interview_notes }}</p>
<p>Best of luck!<br>SuGanta Tutors</p>

