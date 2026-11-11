const Button = ({ children, type = 'button', TagName = 'button', className = '', ...props }) => (
  <TagName
    {...props}
    type={type}
    className={`inline-block py-2.5 px-5 md:py-3 md:px-7 cursor-pointer text-center font-poppins font-medium text-sm md:text-base leading-6 transition-all rounded-tr-2xl rounded-bl-2xl md:rounded-tr-4xl md:rounded-bl-4xl hover:brightness-90 ${className}`}
    style={{ backgroundColor: 'var(--color-button)', color: 'var(--color-button-text)' }}
  >
    {children}
  </TagName>
);

export default Button;
