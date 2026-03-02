<p>Hi {{ $application->applicant_name ?? 'Applicant' }},</p>
<p>Your application for <strong>{{ $application->job->title ?? 'the position' }}</strong> at <strong>{{ $application->job->company_name ?? 'the company' }}</strong> has been marked as withdrawn.</p>
@if(!empty($application->withdrawal_reason))
<p><strong>Reason:</strong> {{ $application->withdrawal_reason }}</p>
@endif
<p>If this is unexpected, please contact support.</p>
<p>Regards,<br>SuGanta Tutors</p>

