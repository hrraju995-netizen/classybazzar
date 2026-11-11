import SubBanner from '@/components/SubBanner'
import UserProfileDashboard from '@/components/UserProfileDashboard'
import React from 'react'

const UserAccountPage = () => {
  return (
    <section>
      <div>
        <SubBanner title={"Your Profile"} pageName={"Profile"} />
      </div>
      <div className="container">
        <div>
          <UserProfileDashboard />
        </div>
      </div>
    </section>
  )
}

export default UserAccountPage
