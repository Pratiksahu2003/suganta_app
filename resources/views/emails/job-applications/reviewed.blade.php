<p>Hi {{ $application->applicant_name ?? 'Applicant' }},</p>
<p>Your application for <strong>{{ $application->job->title ?? 'the position' }}</strong> at <strong>{{ $application->job->company_name ?? 'the company' }}</strong> has been reviewed.</p>
<p>We will keep you updated on the next steps.</p>
<p>Thanks,<br> SuGanta Tutors</p>

