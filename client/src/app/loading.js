import {Spinner} from "@heroui/react";
const loading = () => {
  return ( 
       <div className="flex flex-col items-center gap-2 justify-center h-screen">
        <Spinner size="xl" color="danger"/>
        <span className="text-xs text-muted">Loading...</span>
      </div>
  )
}

export default loading
