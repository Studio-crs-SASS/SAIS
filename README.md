# SAIS - Syu AI Introduction System

SAIS is the Phase 4 Satellite Engine of the SEEN project.

SAIS receives SADS SAISBridge Output and generates introduction proposal data, estimate items, introduction plans, SASS scope candidates, and SASS bridge data.

---

## 1. Project

```text
Project: SEEN
System: SAIS
Official Name: Syu AI Introduction System
Phase: Phase 4
System Type: Satellite Engine
Role: Introduction Proposal Engine
Input System: SADS
Target System: SASS


2. SEEN Flow
SWCS
↓
SADS
↓
SAIS
↓
SASS

SAIS does not execute SWCS.
SAIS does not recalculate SADS scores.
SAIS does not execute SASS.
SAIS does not finalize pricing or contract terms.


3. SAIS Role

SAIS converts SADS diagnosis results into practical introduction proposal data.

SADS SAISBridge Output
↓
SAIS
↓
Proposal
Estimate Items
Introduction Plan
SASS Scope Candidate
SASS Bridge


4. Main Output

SAIS returns JSON with the following main fields.

status
system
version
project
target
source
proposal
estimate
introduction_plan
sass_scope_candidate
additional_check_items
proposal_data
sass_bridge
warnings
metadata
processing


5. Directory Structure
SAIS/
├── Bridge/
├── Check/
├── Classification/
├── Config/
├── Data/
│   ├── input/
│   ├── output/
│   └── samples/
├── Docs/
├── Estimate/
├── Input/
├── Introduction/
├── Output/
├── Priority/
├── Proposal/
├── Public/
├── Scope/
├── Storage/
│   ├── cache/
│   ├── logs/
│   └── reports/
├── Tests/
│   ├── Integration/
│   ├── Samples/
│   └── Unit/
├── bootstrap.php
└── README.md


6. Runtime

Start local server:

cd ~/Desktop/SAIS
php -S localhost:8001 -t Public

API endpoint:

POST http://localhost:8001/api.php


7. API Test

Run API request:

cd ~/Desktop/SAIS
curl -X POST http://localhost:8001/api.php \
  -H "Content-Type: application/json" \
  --data-binary @Data/samples/sads_sais_bridge_sample.json

Save API output:

cd ~/Desktop/SAIS
curl -X POST http://localhost:8001/api.php \
  -H "Content-Type: application/json" \
  --data-binary @Data/samples/sads_sais_bridge_sample.json \
  -o Data/output/sais_api_test_output.json



  8. Tests

Sample input test:

cd ~/Desktop/SAIS
php Tests/Samples/sample_input_test.php

Integration test:

cd ~/Desktop/SAIS
php Tests/Integration/sais_api_pipeline_test.php

Expected final line:

SAIS Integration Test Completed Successfully.



9. Sample Files

Input sample:

Data/samples/sads_sais_bridge_sample.json

Output sample:

Data/samples/sais_output_sample.json

API test output:

Data/output/sais_api_test_output.json



10. Development Policy

SAIS follows the SEEN development policy.

Docs First
Specification Fixed
One File Completed
Syntax Check
Function Check
Integration Test
Git Commit
Git Push



11. Current Status
SAIS Implementation: Completed
Public API: Completed
Sample Input Test: Passed
Integration Test: Passed
SASS Bridge Output: Completed



12. License / Ownership

Studio-crs / SEEN Project

SAIS is developed as part of the SEEN ecosystem.
