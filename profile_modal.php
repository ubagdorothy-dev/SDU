<!-- Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="profileModalLabel">Edit Profile</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="profileForm">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" class="form-control" id="profileFullName" readonly>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" id="profileEmail" readonly>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Position</label>
                <input type="text" class="form-control" id="profilePosition" name="position">
              </div>
              
              <div class="mb-3">
                <label class="form-label">Employment Status</label>
                <select class="form-control" id="profileEmploymentStatus" name="employment_status">
                  <option value="">Select Status</option>
                  <option value="Probationary">Probationary</option>
                  <option value="Permanent/Regular">Permanent/Regular</option>
                </select>
              </div>
            </div>
            
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Program</label>
                <select class="form-control" id="profileProgram" name="program">
                  <option value="">Select Program</option>
                  <option value="Accountancy">Accountancy</option>
                  <option value="Biology">Biology</option>
                  <option value="Business Administration">Business Administration</option>
                  <option value="Chemistry">Chemistry</option>
                  <option value="Communication">Communication</option>
                  <option value="Computer Science">Computer Science</option>
                  <option value="Economics">Economics</option>
                  <option value="Education">Education</option>
                  <option value="English">English</option>
                  <option value="History">History</option>
                  <option value="Mathematics">Mathematics</option>
                  <option value="Nursing">Nursing</option>
                  <option value="Philosophy">Philosophy</option>
                  <option value="Physics">Physics</option>
                  <option value="Political Science">Political Science</option>
                  <option value="Psychology">Psychology</option>
                  <option value="Sociology">Sociology</option>
                  <option value="Theology">Theology</option>
                  <option value="Others">Others</option>
                </select>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Job Function</label>
                <select class="form-control" id="profileJobFunction" name="job_function">
                  <option value="">Select Job Function</option>
                  <option value="Administrative Support">Administrative Support</option>
                  <option value="Research">Research</option>
                  <option value="Teaching">Teaching</option>
                  <option value="Extension Services">Extension Services</option>
                  <option value="Library Services">Library Services</option>
                  <option value="IT Support">IT Support</option>
                  <option value="Finance">Finance</option>
                  <option value="Human Resources">Human Resources</option>
                  <option value="Maintenance">Maintenance</option>
                  <option value="Security">Security</option>
                  <option value="Others">Others</option>
                </select>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Degree Attained</label>
                <select class="form-control" id="profileDegreeAttained" name="degree_attained">
                  <option value="">Select Degree</option>
                  <option value="Bachelors">Bachelors</option>
                  <option value="Masters">Master's</option>
                  <option value="Doctorate">Doctorate / PhD</option>
                  <option value="Others">Others (specify)</option>
                </select>
              </div>
              
              <div class="mb-3" id="profileDegreeOtherContainer" style="display: none;">
                <label class="form-label">Specify Degree</label>
                <input type="text" class="form-control" id="profileDegreeOther" name="degree_other">
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="saveProfileBtn">Save Changes</button>
      </div>
    </div>
  </div>
</div>

<script>
// Handle degree selection change to show/hide other field
document.getElementById('profileDegreeAttained').addEventListener('change', function() {
  const otherContainer = document.getElementById('profileDegreeOtherContainer');
  if (this.value === 'Others') {
    otherContainer.style.display = 'block';
  } else {
    otherContainer.style.display = 'none';
    document.getElementById('profileDegreeOther').value = '';
  }
});

// Initialize profile modal
function initProfileModal(action) {
  if (action === 'view') {
    // Fetch profile data
    fetch('profile_api.php?action=view')
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const profile = data.profile;
          
          // Populate form fields
          document.getElementById('profileFullName').value = profile.full_name || '';
          document.getElementById('profileEmail').value = profile.email || '';
          document.getElementById('profilePosition').value = profile.position || '';
          document.getElementById('profileProgram').value = profile.program || '';
          document.getElementById('profileJobFunction').value = profile.job_function || '';
          document.getElementById('profileEmploymentStatus').value = profile.employment_status || '';
          document.getElementById('profileDegreeAttained').value = profile.degree_attained || '';
          
          // Handle degree other field
          if (profile.degree_attained === 'Others') {
            document.getElementById('profileDegreeOtherContainer').style.display = 'block';
            document.getElementById('profileDegreeOther').value = profile.degree_other || '';
          } else {
            document.getElementById('profileDegreeOtherContainer').style.display = 'none';
            document.getElementById('profileDegreeOther').value = '';
          }
        } else {
          alert('Error loading profile: ' + data.error);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Error loading profile');
      });
  }
}

// Save profile changes
document.getElementById('saveProfileBtn').addEventListener('click', function() {
  const formData = new FormData(document.getElementById('profileForm'));
  formData.append('action', 'update');
  
  fetch('profile_api.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert('Profile updated successfully');
      // Close modal
      bootstrap.Modal.getInstance(document.getElementById('profileModal')).hide();
    } else {
      alert('Error updating profile: ' + data.error);
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Error updating profile');
  });
});
</script>