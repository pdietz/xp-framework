/* This class is part of the XP framework's EAS connectivity
 *
 * $Id$
 */

package net.xp_framework.easc.unittest;

import org.junit.Test;
import net.xp_framework.easc.unittest.Person;
import java.lang.reflect.Method;

import static net.xp_framework.easc.util.MethodMatcher.methodFor;
import static org.junit.Assert.*;

/**
 * Test method matching functionality. 
 *
 * Note: This is a JUnit 4 testcase!
 *
 * @see   net.xp_framework.easc.util.MethodMatcher
 */
public class MethodMatcherTest {

    /**
     * Returns a string representation of a method
     *
     * Format:
     *   [returnType] [methodName]:[numArguments]([optArgumentList])
     *
     * Notes:
     * - returnType is the return type's class name
     * - optArgumentList is a comma-separated list of argument class names
     *
     * Returns the string "(null)" if the specified argument is NULL
     *
     * @see     java.lang.reflect.Method#toString
     * @access  protected
     * @param   java.lang.reflect.Method m
     * @return  java.lang.String
     */
    protected String methodString(Method m) {
        if (m == null) return "(null)";   // Catch border-case, prevent NPE

        Class[] parameters= m.getParameterTypes();
        StringBuffer buf= new StringBuffer()
            .append(m.getReturnType().getName())
            .append(' ')
            .append(m.getName())
            .append(':')
            .append(parameters.length)
            .append("(");
        
        // Append parameter types
        for (Class c: parameters) {
            buf.append(c.getName()).append(", ");
        }
        if (parameters.length > 0) {  
            buf.delete(buf.length() - 2, 2);
        }
        
        return buf.append(')').toString();
    }

    @Test public void noArgMethod() throws Exception {
        assertEquals(
            "int getId:0()", 
            methodString(methodFor(Person.class, "getId", new Object[] { }))
        );
    }
}
