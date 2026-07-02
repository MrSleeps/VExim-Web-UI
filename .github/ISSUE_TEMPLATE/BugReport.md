name: "Bug Report"
fields:
- type: "checkboxes"
  attributes:
    label: "Have you.."
    options:
    - label: "Updated the core web app to the latest version?"
      required: false
    - label: "Updated any/all modules?"
      required: false
    - label: "Not made any modifications to the code?"
      required: false
    - label: "Run php artisan optimize:clear in the vexim_web directory?"
      required: false
  validations:
    required: false
- type: "checkboxes"
  attributes:
    label: "Is this bug report for.."
    options:
    - label: "The web app"
      required: false
    - label: "A core module"
      required: false
    - label: "A plugin"
      required: false
    - label: "Not sure / other"
      required: false
  validations:
    required: false
- type: "textarea"
  attributes:
    label: "Description"
    description: "Give a description of the problem"
  validations:
    required: true
- type: "textarea"
  attributes:
    label: "Logs"
    description: "Paste your storage/laravel/logs/laravel.log output"
  validations:
    required: false
- type: "checkboxes"
  attributes:
    label: "PHP version"
    description: "What version of PHP are you using?"
    options:
    - label: "8.4"
      required: false
    - label: "8.5"
      required: false
    - label: "Other"
      required: false
  validations:
    required: false
- type: "input"
  attributes:
    label: "Database server"
    description: "What database server and version are you using?"
  validations:
    required: true
- type: "textarea"
  attributes:
    label: "Another other information"
    description: "Anything else that would help us debug this error?"
  validations:
    required: false
- type: "checkboxes"
  attributes:
    label: "Irritating checkbox"
    description: "Have you provided as much information as you can, including logs and/or screenshots?"
    options:
    - label: "Yes"
      required: true
  validations:
    required: false
