import java.io.File;
import java.io.FileInputStream;
import java.io.FileWriter;
import java.io.IOException;

import org.apache.tika.exception.TikaException;
import org.apache.tika.metadata.Metadata;
import org.apache.tika.parser.ParseContext;
import org.apache.tika.parser.html.HtmlParser;
import org.apache.tika.sax.BodyContentHandler;
import org.xml.sax.SAXException;

public class BigTextGenerator {

   public static void main(final String[] args) throws IOException,SAXException, TikaException {
	   
	  FileWriter big_text = new FileWriter("/Users/nehapathapati/Desktop/BigText_BG.txt");
	  File dir = new File("/Users/nehapathapati/Desktop/CSCI 572 - IR/Homework 4/BG/BG/");
	  
	  for(File file : dir.listFiles()) {
		  
		  FileInputStream inputstream = new FileInputStream(file);
	   
		  BodyContentHandler handler = new BodyContentHandler(-1);
	      Metadata metadata = new Metadata();
	      ParseContext pcontext = new ParseContext();   
	      
	      HtmlParser htmlparser = new HtmlParser();
	      htmlparser.parse(inputstream, handler, metadata, pcontext);
	      String document_content = handler.toString().trim().replaceAll("\\s+", " ");
	      
	      big_text.append(document_content + " ");
	  }
	  big_text.close();
   }
}