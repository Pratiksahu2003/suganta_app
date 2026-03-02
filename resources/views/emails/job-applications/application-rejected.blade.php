<p>Hi {{ $application->applicant_name ?? 'Applicant' }},</p>
<p>We appreciate your interest in <strong>{{ $application->job->title ?? 'the position' }}</strong> at <strong>{{ $application->job->company_name ?? 'the company' }}</strong>. After careful review, we will not be moving forward.</p>
@if(!empty($application->rejection_reason))
<p><strong>Reason:</strong> {{ $application->rejection_reason }}</p>
@endif
<p>Thank you for applying and we wish you all the best.</p>
<p>Regards,<br>SuGanta Tutors</p>

