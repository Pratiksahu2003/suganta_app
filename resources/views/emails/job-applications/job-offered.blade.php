<p>Hi {{ $application->applicant_name ?? 'Applicant' }},</p>
<p>Congratulations! You have been offered the role of <strong>{{ $application->job->title ?? 'the position' }}</strong> at <strong>{{ $application->job->company_name ?? 'the company' }}</strong>.</p>
<p>Please check your dashboard or contact us for next steps.</p>
<p>Thanks,<br>SuGanta Tutors</p>

